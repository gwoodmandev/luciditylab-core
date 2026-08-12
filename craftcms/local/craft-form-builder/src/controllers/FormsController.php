<?php
namespace luciditylab\craftFormBuilder\controllers;

use Craft;
use craft\helpers\ElementHelper;
use craft\web\Controller;
use luciditylab\craftFormBuilder\elements\Form;
use yii\web\Response;
use yii\web\ServerErrorHttpException;

class FormsController extends Controller
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

    public function actionIndex(): Response
    {
        $this->requirePermission('formBuilder:viewForms');

        return $this->renderTemplate('form-builder/_cp/index');
    }

    public function actionCreate(): Response
    {
        $this->requirePermission('formBuilder:saveForms');

        $form = new Form();
        $form->title = Craft::t('form-builder', 'Untitled form');
        $form->slug = ElementHelper::tempSlug();
        $form->enabled = true;

        if (!Craft::$app->getElements()->saveElement($form, false)) {
            throw new ServerErrorHttpException(
                'Could not create a form: ' . implode(', ', $form->getErrorSummary(true))
            );
        }

        return $this->redirect($form->getCpEditUrl());
    }
}
