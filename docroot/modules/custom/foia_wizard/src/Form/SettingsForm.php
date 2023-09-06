<?php

namespace Drupal\foia_wizard\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure FOIA Request Wizard settings for this site.
 */
class SettingsForm extends ConfigFormBase {

  const M_COUNT = 60;

  private $message_mapping = [];

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    parent::__construct($config_factory);

    $this->message_mapping = [
      'topic0' => [
        'description' => $this->t('These items are shared by multiple topics.'),
        'title' => $this->t('Shared'),
        'messages' => [
          'm1',
          'm2',
        ],
      ],
      'topic1' => [
        'title' => $this->t('Immigration records'),
        'description' => '',
        'messages' => [
          'm3',
          'm4',
          'm5',
          'm6',
          'm7',
          'm8',
          'm9',
          'm10',
          'm11',
          'm12',
          'm13',
          'm14',
          'm15',
        ],
      ],
      'topic2' => [
        'title' => $this->t('Tax records'),
        'description' => '',
        'messages' => [
          'm16',
          'm17',
          'm18',
        ],
      ],
      'topic3' => [
        'title' => $this->t('Travel records'),
        'description' => '',
        'messages' => [
          'm3',
          'm4',
          'm5',
          'm6',
          'm7',
          'm8',
          'm9',
          'm10',
          'm11',
          'm12',
          'm13',
          'm14',
          'm15',
        ],
      ],
      'topic4' => [
        'title' => $this->t('Social Security records'),
        'description' => '',
        'messages' => [
          'm19',
          'm20',
          'm21',
          'm22',
          'm47',
        ],
      ],
      'topic5' => [
        'title' => $this->t('Medical records'),
        'description' => '',
        'messages' => [
          'm23',
          'm24',
          'm25',
          'm26',
          'm27',
          'm28',
          'm29',
          'm30',
        ],
      ],
      'topic6' => [
        'title' => $this->t('Personnel records'),
        'description' => '',
        'messages' => [
          'm31',
          'm32',
          'm33',
          'm34',
          'm35',
        ],
      ],
      'topic7' => [
        'title' => $this->t('Military records'),
        'description' => '',
        'messages' => [
          'm25',
          'm36',
          'm37',
          'm38',
          'm39',
          'm40',
          'm41',
          'm42',
          'm43',
          'm44',
          'm45',
          'm46',
        ],
      ],
    ];

  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'foia_wizard_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['foia_wizard.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    // Add description markup to the form.
    $form['description'] = [
      '#markup' => $this->t('This form allows you to configure the FOIA Request Wizard.'),
    ];

    // Hacky but huge UX improvement.
    $form['CSS'] = [
      '#children' => '<style>.foia-wizard-settings .vertical-tabs__menu {max-height: 30rem; overflow-y: auto; overflow-x:hidden}</style>',
    ];

    $form['#tree'] = TRUE;

    // Introduction title slide.
    $form['intro_slide'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Introduction Slide'),
      '#required' => TRUE,
      '#default_value' => $this->config('foia_wizard.settings')->get('intro_slide.value'),
      '#format' => $this->config('foia_wizard.settings')->get('intro_slide.format'),
      '#description' => $this->t('This is the text that will appear on the first slide of the FOIA Request Wizard.'),
    ];

    // Beginning query slide.
    $form['query_slide'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Query Slide'),
      '#required' => TRUE,
      '#default_value' => $this->config('foia_wizard.settings')->get('query_slide.value'),
      '#format' => $this->config('foia_wizard.settings')->get('query_slide.format'),
      '#description' => $this->t('This is the text that will appear on the query slide of the FOIA Request Wizard.'),
    ];

    // Messages tab group.
    $form['topic_tabs'] = [
      '#title' => '<h3>' . $this->t('Topics') . '</h3>',
      '#description' => $this->t('This text will be used within the FOIA Request Wizard.'),
      '#type' => 'vertical_tabs',
    ];

    foreach ($this->message_mapping as $key => $topic) {
      $form['topic_tabs'][$key] = [
        '#type' => 'details',
        '#title' => $topic['title'],
        '#description' => $topic['description'],
        '#group' => 'topic_tabs',
      ];
      foreach ($topic['messages'] as $mid) {
        $form['topic_tabs'][$key][$mid] = [
          '#type' => 'text_format',
          '#title' => $this->t('Message @i', ['@i' => $mid]),
          '#default_value' => $this->config('foia_wizard.settings')->get('messages')[$mid]['value'],
          '#group' => $key,
          '#format' => $this->config('foia_wizard.settings')->get('messages')[$mid]['format'],
          '#description' => $this->t('This text will be used as key <code>@i</code> within the FOIA Request Wizard.', ['@i' => $mid]),
        ];
      }
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    // Collect message field values.
    $messages = [];
    foreach ($this->message_mapping as $key => $topic) {
      foreach ($topic['messages'] as $mid) {
        $messages[$mid] = $form_state->getValue([
          'topic_tabs',
          $key,
          $mid,
        ]);
      }
    }

    // Save all the form fields.
    $this->config('foia_wizard.settings')
      ->set('intro_slide', $form_state->getValue('intro_slide'))
      ->set('query_slide', $form_state->getValue('query_slide'))
      ->set('messages', $messages)
      ->save();

    parent::submitForm($form, $form_state);
  }

}
