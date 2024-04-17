<?php

namespace Drupal\warpwire\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provide the (global) Warpwire settings form, accessed from admin Configuration page.
 */
class WarpwireDefaultSettingsForm extends ConfigFormBase
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

        // Default video display settings
        $form['video_defaults_fieldset']['default_video_display_share'] = array(
            '#type' => 'checkbox',
            '#title' => t('Show share button by default'),
            '#default_value' => $config->get('warpwire.default_video_display_share'),
            '#description' => t('Display a share button on every media item unless its embed URL indicates otherwise.'),
        );

        $form['video_defaults_fieldset']['default_video_display_title'] = array(
            '#type' => 'checkbox',
            '#title' => t('Show title by default'),
            '#default_value' => $config->get('warpwire.default_video_display_title'),
            '#description' => t('Display a title on every media item unless its embed URL indicates otherwise.'),
        );

        $form['video_defaults_fieldset']['default_video_display_autoplay'] = array(
            '#type' => 'checkbox',
            '#title' => t('Autoplay by default'),
            '#default_value' => $config->get('warpwire.default_video_display_autoplay'),
            '#description' => t('Autoplay every media item unless its embed URL indicates otherwise.'),
        );

        $form['video_defaults_fieldset']['default_video_display_cc'] = array(
            '#type' => 'checkbox',
            '#title' => t('Show closed captions button by default'),
            '#default_value' => $config->get('warpwire.default_video_display_cc'),
            '#description' => t('Display a closed captions button on any media item that has captions, unless the embed URL indicates otherwise.'),
        );

        $form['video_defaults_fieldset']['default_video_width'] = array(
            '#type' => 'textfield',
            '#title' => t('Default video width'),
            '#default_value' => $config->get('warpwire.default_video_width'),
            '#description' => t('The width of the video player in pixels, unless the embed URL indicates other dimensions.'),
        );

        $form['video_defaults_fieldset']['default_video_height'] = array(
            '#type' => 'textfield',
            '#title' => t('Default video height'),
            '#default_value' => $config->get('warpwire.default_video_height'),
            '#description' => t('The height of the video player in pixels, unless the embed URL indicates other dimensions.'),
        );

        return $form;
    }


    public function validateForm(array &$form, FormStateInterface $form_state)
    {
        $config = $this->config('warpwire.settings');
        $min_video_width = $config->get('warpwire.min_video_width');
        $min_video_height = $config->get('warpwire.min_video_height');

        if (
            $form_state->getValue('default_video_width') == NULL ||
            !is_numeric($form_state->getValue('default_video_width')) ||
            $form_state->getValue('default_video_width') < $min_video_width
        ) {
            $form_state->setErrorByName(
                'default_video_width',
                t('Please enter a value of ' . $min_video_width . ' or greater for default video width.')
            );
        }
        if (
            $form_state->getValue('default_video_height') == NULL ||
            !is_numeric($form_state->getValue('default_video_height')) ||
            $form_state->getValue('default_video_height') < $min_video_height
        ) {
            $form_state->setErrorByName(
                'default_video_height',
                t('Please enter a value of ' . $min_video_height . ' or greater for default video height.')
            );
        }
    }


    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        $config = $this->config('warpwire.settings');
        $config->set('warpwire.default_video_display_share', $form_state->getValue('default_video_display_share'));
        $config->set('warpwire.default_video_display_title', $form_state->getValue('default_video_display_title'));
        $config->set('warpwire.default_video_display_autoplay', $form_state->getValue('default_video_display_autoplay'));
        $config->set('warpwire.default_video_display_cc', $form_state->getValue('default_video_display_cc'));
        $config->set('warpwire.default_video_width', (int)$form_state->getValue('default_video_width'));
        $config->set('warpwire.default_video_height', (int)$form_state->getValue('default_video_height'));
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
