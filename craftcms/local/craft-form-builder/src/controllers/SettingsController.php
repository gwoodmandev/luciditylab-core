<?php
namespace luciditylab\craftFormBuilder\controllers;

use Craft;
use craft\web\Controller;
use luciditylab\craftFormBuilder\elements\Form;
use yii\web\Response;

class SettingsController extends Controller
{
    public function beforeAction($action): bool
    {
        if (!parent::beforeAction($action)) {
            return false;
        }

        $this->requireCpRequest();
        $this->requireAdmin();

        return true;
    }

    public function actionIndex(): Response
    {
        return $this->renderTemplate('form-builder/_cp/settings', [
            'fieldLayout' => Craft::$app->getFields()->getLayoutByType(Form::class),
        ]);
    }

    public function actionSaveFieldLayout(): ?Response
    {
        $this->requirePostRequest();

        $fieldLayout = Craft::$app->getFields()->assembleLayoutFromPost();
        $fieldLayout->type = Form::class;

        if (!Craft::$app->getFields()->saveLayout($fieldLayout)) {
            $this->setFailFlash(Craft::t('form-builder', 'Couldn’t save form fields.'));
            Craft::$app->getUrlManager()->setRouteParams(['fieldLayout' => $fieldLayout]);

            return null;
        }

        $this->setSuccessFlash(Craft::t('form-builder', 'Form fields saved.'));

        return $this->redirectToPostedUrl();
    }
}
