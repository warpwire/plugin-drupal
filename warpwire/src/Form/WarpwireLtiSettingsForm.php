<?php

namespace Drupal\warpwire\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provide the (global) Warpwire settings form, accessed from admin Configuration page.
 */
class WarpwireLtiSettingsForm extends ConfigFormBase
{

    public function getFormId()
    {
        return 'warpwire_form';
    }

    public function buildForm(array $form, FormStateInterface $form_state)
    {
        // Form constructor
        $form = parent::buildForm($form, $form_state);
        // Default settings
        $config = $this->config('warpwire.settings');


        $form['lti_settings_fieldset'] = array(
            '#type' => 'fieldset',
            '#title' => t('LTI launch settings'),
            '#collapsible' => FALSE,
            '#collapsed' => FALSE,
        );

        // Warpwire Site URL
        $form['lti_settings_fieldset']['lti_url'] = array(
            '#type' => 'textfield',
            '#title' => t('Warpwire Site URL:'),
            '#placeholder' => 'https://example.warpwire.com',
            '#default_value' => $config->get('warpwire.lti_url'),
            '#description' => t('The base URL of your Warpwire instance'),
        );

        // LTI Key
        $form['lti_settings_fieldset']['lti_key'] = array(
            '#type' => 'textfield',
            '#title' => t('LTI Key:'),
            '#default_value' => $config->get('warpwire.lti_key'),
            '#description' => t('Your LTI key from Warpwire (see: <a href="https://www.warpwire.com/support/admin/external-keys/#lti" target="_blank">Warpwire documentation</a>)'),
        );

        // LTI Secret
        $form['lti_settings_fieldset']['lti_secret'] = array(
            '#type' => 'textfield',
            '#title' => t('LTI Secret:'),
            '#default_value' => $config->get('warpwire.lti_secret'),
            '#description' => t('Your LTI secret from Warpwire'),
        );

        $form['lti_mapping_settings_fieldset'] = array(
            '#type' => 'fieldset',
            '#title' => t('LTI mapping settings'),
            '#collapsible' => FALSE,
            '#collapsed' => FALSE,
        );

        // LTI Institution Name
        $form['lti_mapping_settings_fieldset']['lti_institution_name'] = array(
            '#type' => 'textfield',
            '#title' => t('Institution Name:'),
            '#placeholder' => 'Drupal',
            '#default_value' => $config->get('warpwire.lti_institution_name'),
            '#description' => t('This information will be attached to users created in Warpwire.'),
        );

        // Warpwire Group Name
        // What group do the Drupal users go into in WW so that they can be identified
        $form['lti_mapping_settings_fieldset']['group_name'] = array(
            '#type' => 'textfield',
            '#title' => t('Warpwire Group Name:'),
            '#placeholder' => 'Drupal',
            '#default_value' => $config->get('warpwire.group_name'),
            '#description' => t('All new users created via LTI launch from Drupal will be added to this group in Warpwire.'),
        );

        return $form;
    }


    public function validateForm(array &$form, FormStateInterface $form_state)
    {
        $config = $this->config('warpwire.settings');

        if ($form_state->getValue('lti_key') == NULL) {
            $form_state->setErrorByName('lti_key', t('Please enter your LTI key.'));
        }
        if ($form_state->getValue('lti_secret') == NULL) {
            $form_state->setErrorByName('lti_secret', t('Please enter your LTI secret.'));
        }
        if ($form_state->getValue('lti_url') == NULL) {
            $form_state->setErrorByName('lti_url', t('Please enter your LTI URL.'));
        }
        if ($form_state->getValue('group_name') == NULL) {
            $form_state->setErrorByName('group_name', t('Please enter your Warpwire Group Name.'));
        }
    }


    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        $config = $this->config('warpwire.settings');
        $config->set('warpwire.lti_key', $form_state->getValue('lti_key'));
        $config->set('warpwire.lti_secret', $form_state->getValue('lti_secret'));
        $config->set('warpwire.lti_url', $form_state->getValue('lti_url'));
        $config->set('warpwire.lti_institution_name', $form_state->getValue('lti_institution_name'));
        $config->set('warpwire.group_name', $form_state->getValue('group_name'));
        $config->save();
        // TODO: Replace this later with a less brute force option
        // This is required because these settings are used in a filter and the filter is cached.
        drupal_flush_all_caches();
        return parent::submitForm($form, $form_state);
    }


    protected function getEditableConfigNames()
    {
        return [
            'warpwire.settings',
        ];
    }
}
