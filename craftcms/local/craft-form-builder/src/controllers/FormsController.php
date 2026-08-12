<?php
namespace luciditylab\craftFormBuilder\controllers;

use Craft;
use craft\helpers\ElementHelper;
use craft\web\Controller;
use luciditylab\craftFormBuilder\elements\Form;
use yii\web\Response;
use yii\web\ServerErrorHttpException;

/**
 * Control panel actions for the Forms section.
 *
 * Craft's own ElementsController handles editing, saving, deleting and
 * duplicating, so only the index and "new form" actions live here.
 */
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

    /**
     * Creates an empty form and drops the editor straight into it.
     *
     * Form elements do not support drafts, so this saves a real element with a
     * temporary slug rather than routing through elements/create.
     */
    public function actionCreate(): Response
    {
        $this->requirePermission('formBuilder:saveForms');

        $form = new Form();
        $form->title = Craft::t('form-builder', 'Untitled form');
        $form->slug = ElementHelper::tempSlug();
        $form->enabled = true;

        // the field layout is likely to have required fields, so skip
        // validation on this initial save and let the edit screen enforce them
        if (!Craft::$app->getElements()->saveElement($form, false)) {
            throw new ServerErrorHttpException(
                'Could not create a form: ' . implode(', ', $form->getErrorSummary(true))
            );
        }

        return $this->redirect($form->getCpEditUrl());
    }
}
