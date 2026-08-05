<?php
namespace luciditylab\craftFormBuilder\controllers;

use Craft;
use craft\elements\Entry;
use craft\web\Controller;
use luciditylab\craftFormBuilder\FormBuilderPlugin;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

/**
 * Control panel actions for reading and managing submissions.
 */
class SubmissionsController extends Controller
{
    public function beforeAction($action): bool
    {
        if (!parent::beforeAction($action)) {
            return false;
        }

        $this->requireCpRequest();
        $this->requireLogin();

        return true;
    }

    /**
     * Returns a single submission's detail as HTML, for the slideout.
     */
    public function actionDetail(): Response
    {
        $this->requireAcceptsJson();

        $id = (int)Craft::$app->getRequest()->getRequiredParam('id');
        $plugin = FormBuilderPlugin::getInstance();
        $submission = $plugin->submissions->getById($id);

        if (!$submission) {
            throw new NotFoundHttpException('Submission not found.');
        }

        $form = $this->getAuthorisedForm($submission->formId);

        // opening a submission marks it read
        if (!$submission->isRead()) {
            $plugin->submissions->markAsRead($id);
        }

        return $this->asJson([
            'html' => Craft::$app->getView()->renderTemplate('form-builder/_cp/detail', [
                'submission' => $submission,
                'form' => $form,
                'definitions' => $plugin->forms->getFieldDefinitions($form),
            ], \craft\web\View::TEMPLATE_MODE_CP),
            'unread' => $plugin->submissions->getUnreadCountByFormId($submission->formId),
        ]);
    }

    public function actionToggleRead(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $request = Craft::$app->getRequest();
        $id = (int)$request->getRequiredBodyParam('id');
        $read = (bool)$request->getBodyParam('read', true);

        $plugin = FormBuilderPlugin::getInstance();
        $submission = $plugin->submissions->getById($id);

        if (!$submission) {
            throw new NotFoundHttpException('Submission not found.');
        }

        $this->getAuthorisedForm($submission->formId);
        $plugin->submissions->markAsRead($id, $read);

        return $this->asJson([
            'success' => true,
            'unread' => $plugin->submissions->getUnreadCountByFormId($submission->formId),
        ]);
    }

    public function actionMarkAllRead(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $formId = (int)Craft::$app->getRequest()->getRequiredBodyParam('formId');
        $this->getAuthorisedForm($formId);

        $count = FormBuilderPlugin::getInstance()->submissions->markAllAsRead($formId);

        return $this->asJson(['success' => true, 'marked' => $count, 'unread' => 0]);
    }

    public function actionDelete(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $id = (int)Craft::$app->getRequest()->getRequiredBodyParam('id');
        $plugin = FormBuilderPlugin::getInstance();
        $submission = $plugin->submissions->getById($id);

        if (!$submission) {
            throw new NotFoundHttpException('Submission not found.');
        }

        $form = $this->getAuthorisedForm($submission->formId);

        // deleting content requires more than view access
        if (!Craft::$app->getUser()->checkPermission("saveEntries:{$form->section->uid}")) {
            throw new ForbiddenHttpException('You are not permitted to delete submissions for this form.');
        }

        $plugin->submissions->delete($id);

        return $this->asJson([
            'success' => true,
            'unread' => $plugin->submissions->getUnreadCountByFormId($submission->formId),
        ]);
    }

    /**
     * Loads the form entry and confirms the current user may view it.
     */
    private function getAuthorisedForm(int $formId): Entry
    {
        $form = Entry::find()->id($formId)->status(null)->one();

        if (!$form) {
            throw new NotFoundHttpException('Form not found.');
        }

        // reuse Craft's own view authorisation for the underlying entry
        if (!Craft::$app->getElements()->canView($form)) {
            throw new ForbiddenHttpException('You are not permitted to view this form’s submissions.');
        }

        return $form;
    }
}
