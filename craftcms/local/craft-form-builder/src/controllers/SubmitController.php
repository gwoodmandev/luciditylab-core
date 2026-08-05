<?php
namespace luciditylab\craftFormBuilder\controllers;

use Craft;
use craft\elements\Entry;
use craft\helpers\App;
use craft\helpers\Assets;
use craft\web\Controller;
use craft\web\UploadedFile;
use luciditylab\craftFormBuilder\FormBuilderPlugin;
use yii\web\BadRequestHttpException;
use yii\web\Response;

/**
 * Handles public form submissions.
 */
class SubmitController extends Controller
{
    protected array|int|bool $allowAnonymous = ['index'];

    /**
     * Minimum seconds between a form being rendered and submitted. Anything
     * faster is almost certainly automated.
     */
    private const MIN_ELAPSED_SECONDS = 3;

    public function actionIndex(): ?Response
    {
        $this->requirePostRequest();

        $request = Craft::$app->getRequest();
        $formId = (int)$request->getBodyParam('formId');

        $form = Entry::find()
            ->id($formId)
            ->section('forms')
            ->status(null)
            ->one();

        if (!$form) {
            throw new BadRequestHttpException('Form not found.');
        }

        $formsService = FormBuilderPlugin::getInstance()->forms;
        $definitions = $formsService->getFieldDefinitions($form);

        // ---- spam checks (silently accept, so bots get no feedback to tune against)
        if ($form->enableCaptcha && $this->looksLikeSpam($request)) {
            Craft::info("Form Builder: discarded suspected spam submission for form {$form->id}", __METHOD__);

            return $this->successResponse($form, []);
        }

        // ---- collect + validate
        [$payload, $errors] = $this->collect($definitions, $request);

        if (!empty($errors)) {
            return $this->failureResponse($form, $payload, $errors);
        }

        // ---- persist
        $submission = FormBuilderPlugin::getInstance()->submissions->save(
            $form,
            $payload,
            $request->getUserIP(),
            $request->getUserAgent()
        );

        // ---- notify (failures are logged but never block the visitor)
        try {
            $this->sendNotification($form, $payload, $definitions);
        } catch (\Throwable $e) {
            Craft::error("Form Builder: notification email failed for submission {$submission->id}: {$e->getMessage()}", __METHOD__);
        }

        if ($form->sendConfirmation) {
            try {
                $this->sendConfirmation($form, $payload, $definitions);
            } catch (\Throwable $e) {
                Craft::error("Form Builder: confirmation email failed for submission {$submission->id}: {$e->getMessage()}", __METHOD__);
            }
        }

        return $this->successResponse($form, $payload);
    }

    /**
     * Honeypot must be empty and the form must not have been submitted
     * implausibly quickly.
     */
    private function looksLikeSpam($request): bool
    {
        if (trim((string)$request->getBodyParam('fb_hp', '')) !== '') {
            return true;
        }

        $rendered = $request->getBodyParam('fb_ts');

        if (!$rendered) {
            return true;
        }

        // the timestamp is signed on render, so a bot can't just fabricate one
        $decoded = Craft::$app->getSecurity()->validateData((string)$rendered);

        if ($decoded === false) {
            return true;
        }

        return (time() - (int)$decoded) < self::MIN_ELAPSED_SECONDS;
    }

    /**
     * @param array<int, array<string, mixed>> $definitions
     * @return array{0: array<string, mixed>, 1: array<string, string>}
     */
    private function collect(array $definitions, $request): array
    {
        $payload = [];
        $errors = [];

        foreach ($definitions as $def) {
            if (empty($def['isInput'])) {
                continue;
            }

            $handle = $def['handle'];
            $label = $def['label'] !== '' ? $def['label'] : $handle;

            if ($def['type'] === 'formFieldFile') {
                $file = UploadedFile::getInstanceByName("fields[{$handle}]");

                if (!$file) {
                    if ($def['required']) {
                        $errors[$handle] = "{$label} is required.";
                    }
                    continue;
                }

                $error = $this->validateFile($file, $def, $label);

                if ($error !== null) {
                    $errors[$handle] = $error;
                    continue;
                }

                $payload[$handle] = $this->storeFile($file);
                continue;
            }

            $value = $request->getBodyParam("fields.{$handle}");

            // checkboxes and multi-selects arrive as arrays
            if (is_array($value)) {
                $value = array_values(array_filter(array_map('strval', $value), fn($v) => $v !== ''));
                $empty = $value === [];
            } else {
                $value = trim((string)($value ?? ''));
                $empty = $value === '';
            }

            if ($def['required'] && $empty) {
                $errors[$handle] = "{$label} is required.";
                continue;
            }

            if (!$empty && is_string($value)) {
                $error = $this->validateScalar($value, $def, $label);

                if ($error !== null) {
                    $errors[$handle] = $error;
                    continue;
                }
            }

            $payload[$handle] = $value;
        }

        return [$payload, $errors];
    }

    /**
     * @param array<string, mixed> $def
     */
    private function validateScalar(string $value, array $def, string $label): ?string
    {
        if (!empty($def['maxLength']) && mb_strlen($value) > (int)$def['maxLength']) {
            return "{$label} must be {$def['maxLength']} characters or fewer.";
        }

        return match ($def['inputType'] ?? 'text') {
            'email' => filter_var($value, FILTER_VALIDATE_EMAIL) ? null : "{$label} must be a valid email address.",
            'url' => filter_var($value, FILTER_VALIDATE_URL) ? null : "{$label} must be a valid URL.",
            'number' => is_numeric($value) ? null : "{$label} must be a number.",
            default => null,
        };
    }

    /**
     * @param array<string, mixed> $def
     */
    private function validateFile(UploadedFile $file, array $def, string $label): ?string
    {
        $maxBytes = (int)$def['maxFileSize'] * 1024 * 1024;

        if ($file->size > $maxBytes) {
            return "{$label} must be {$def['maxFileSize']}MB or smaller.";
        }

        $extension = strtolower((string)$file->getExtension());
        $allowed = $def['fileTypes'];

        // never accept an extension Craft itself considers unsafe, even if
        // the editor listed it
        if (!in_array($extension, Craft::$app->getConfig()->getGeneral()->allowedFileExtensions, true)) {
            return "{$label} has a file type that is not permitted.";
        }

        if (!empty($allowed) && !in_array($extension, $allowed, true)) {
            return "{$label} must be one of the following types: " . implode(', ', $allowed) . '.';
        }

        return null;
    }

    /**
     * Moves the upload into a private directory outside the web root and
     * returns the stored filename.
     */
    private function storeFile(UploadedFile $file): string
    {
        $dir = Craft::$app->getPath()->getStoragePath() . DIRECTORY_SEPARATOR . 'form-builder';

        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $filename = Assets::prepareAssetName($file->name);
        $unique = date('YmdHis') . '-' . bin2hex(random_bytes(4)) . '-' . $filename;

        $file->saveAs($dir . DIRECTORY_SEPARATOR . $unique, false);

        return $unique;
    }

    /**
     * @param array<string, mixed> $payload
     * @param array<int, array<string, mixed>> $definitions
     */
    private function sendNotification(Entry $form, array $payload, array $definitions): void
    {
        $plugin = FormBuilderPlugin::getInstance();
        $recipients = $plugin->forms->getRecipients($form);

        if (empty($recipients)) {
            Craft::warning("Form Builder: form {$form->id} has no valid recipients; notification skipped", __METHOD__);
            return;
        }

        $subject = (string)($form->emailSubject ?? '');
        $subject = $subject !== ''
            ? $plugin->forms->interpolate($subject, $payload, $form)
            : "New submission from {$form->title}";

        $body = Craft::$app->getView()->renderTemplate(
            'form-builder/_email/notification',
            [
                'form' => $form,
                'payload' => $payload,
                'definitions' => $definitions,
            ],
            \craft\web\View::TEMPLATE_MODE_CP
        );

        $message = Craft::$app->getMailer()->compose()
            ->setTo($recipients)
            ->setSubject($subject)
            ->setHtmlBody($body)
            ->setTextBody(strip_tags($body));

        // From must stay on a domain we're authorised to send for, or SPF/DMARC
        // will reject the message. The visitor's address goes in Reply-To.
        $fromAddress = trim((string)($form->fromAddress ?? ''));

        if ($fromAddress !== '') {
            $fromName = trim((string)($form->fromName ?? ''));
            $message->setFrom($fromName !== '' ? [$fromAddress => $fromName] : $fromAddress);
        }

        $replyTo = trim((string)($form->replyToAddress ?? ''));

        if ($replyTo === '') {
            $replyTo = $this->findSubmitterEmail($payload, $definitions) ?? '';
        }

        if ($replyTo !== '' && filter_var($replyTo, FILTER_VALIDATE_EMAIL)) {
            $message->setReplyTo($replyTo);
        }

        $message->send();
    }

    /**
     * @param array<string, mixed> $payload
     * @param array<int, array<string, mixed>> $definitions
     */
    private function sendConfirmation(Entry $form, array $payload, array $definitions): void
    {
        $to = $this->findSubmitterEmail($payload, $definitions);

        if ($to === null) {
            Craft::warning("Form Builder: form {$form->id} has confirmations enabled but no email field to send to", __METHOD__);
            return;
        }

        $plugin = FormBuilderPlugin::getInstance();
        $body = (string)($form->confirmationMessage ?? '');

        if (trim($body) === '') {
            return;
        }

        $message = Craft::$app->getMailer()->compose()
            ->setTo($to)
            ->setSubject("Thank you — {$form->title}")
            ->setTextBody($plugin->forms->interpolate($body, $payload, $form));

        $fromAddress = trim((string)($form->fromAddress ?? ''));

        if ($fromAddress !== '') {
            $fromName = trim((string)($form->fromName ?? ''));
            $message->setFrom($fromName !== '' ? [$fromAddress => $fromName] : $fromAddress);
        }

        $message->send();
    }

    /**
     * Finds the visitor's own email address by looking for the first field
     * with an email input type.
     *
     * @param array<string, mixed> $payload
     * @param array<int, array<string, mixed>> $definitions
     */
    private function findSubmitterEmail(array $payload, array $definitions): ?string
    {
        foreach ($definitions as $def) {
            if (empty($def['isInput']) || ($def['inputType'] ?? null) !== 'email') {
                continue;
            }

            $value = $payload[$def['handle']] ?? null;

            if (is_string($value) && filter_var($value, FILTER_VALIDATE_EMAIL)) {
                return $value;
            }
        }

        return null;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function successResponse(Entry $form, array $payload): ?Response
    {
        $redirect = $form->redirectUrl->value ?? null;

        if ($redirect) {
            return $this->redirect($redirect);
        }

        Craft::$app->getSession()->setFlash('formBuilderSuccess', [
            'formId' => $form->id,
            'message' => (string)($form->successMessage ?? 'Thank you, your message has been sent.'),
        ]);

        return $this->redirectToPostedUrl();
    }

    /**
     * @param array<string, mixed> $payload
     * @param array<string, string> $errors
     */
    private function failureResponse(Entry $form, array $payload, array $errors): ?Response
    {
        Craft::$app->getUrlManager()->setRouteParams([
            'formBuilderErrors' => [$form->id => $errors],
            'formBuilderValues' => [$form->id => $payload],
        ]);

        Craft::$app->getSession()->setError('Please check the form for errors.');

        return null;
    }
}
