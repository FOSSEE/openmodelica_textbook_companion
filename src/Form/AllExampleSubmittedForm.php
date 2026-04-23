<?php

namespace Drupal\textbook_companion\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Database;
use Drupal\Core\Messenger\MessengerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Session\AccountProxyInterface;

class AllExampleSubmittedForm extends FormBase {

  protected $mailManager;
  protected $configFactory;
  protected $currentUser;

  public function __construct(MailManagerInterface $mail_manager, ConfigFactoryInterface $config_factory, AccountProxyInterface $current_user) {
    $this->mailManager = $mail_manager;
    $this->configFactory = $config_factory;
    $this->currentUser = $current_user;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.mail'),
      $container->get('config.factory'),
      $container->get('current_user')
    );
  }

  public function getFormId() {
    return 'all_example_submitted_check_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state, $preference_id = NULL) {

    $form['all_example_submitted'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('I have submitted codes for all the examples'),
      '#description' => $this->t('Once you have submitted this option you are not able to upload more examples.'),
      '#required' => TRUE,
    ];

    $form['hidden_preference_id'] = [
      '#type' => 'hidden',
      '#value' => $preference_id,
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    return $form;
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue('all_example_submitted') != 1) {
      $form_state->setErrorByName('all_example_submitted',
        $this->t('Please check the field if you are interested to submit all uploaded examples for review!')
      );
    }
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {

    if ($form_state->getValue('all_example_submitted') == 1) {

      $connection = Database::getConnection();

      // Update query
      $connection->update('textbook_companion_preference')
        ->fields(['submited_all_examples_code' => 1])
        ->condition('id', $form_state->getValue('hidden_preference_id'))
        ->execute();

      // Fetch proposal_id
      $proposal_data = $connection->select('textbook_companion_preference', 'tcp')
        ->fields('tcp', ['proposal_id'])
        ->condition('id', $form_state->getValue('hidden_preference_id'))
        ->execute()
        ->fetchObject();

      // Email config
      $config = $this->configFactory->get('textbook_companion.settings');
      $email_to = \Drupal\user\Entity\User::load($this->currentUser->id())->getEmail();
      $from = $config->get('textbook_companion_from_email');
      $bcc = $config->get('textbook_companion_emails');
      $cc = $config->get('textbook_companion_cc_emails');

      $params['all_code_submitted']['proposal_id'] = $proposal_data->proposal_id;
      $params['all_code_submitted']['user_id'] = $this->currentUser->id();
      $params['headers'] = [
        'From' => $from,
        'MIME-Version' => '1.0',
        'Content-Type' => 'text/plain; charset=UTF-8; format=flowed; delsp=yes',
        'Content-Transfer-Encoding' => '8Bit',
        'X-Mailer' => 'Drupal',
        'Cc' => $cc,
        'Bcc' => $bcc,
      ];

      $result = $this->mailManager->mail(
        'textbook_companion',
        'all_code_submitted',
        $email_to,
        $this->currentUser->getPreferredLangcode(),
        $params,
        $from,
        TRUE
      );

      if (!$result['result']) {
        $this->messenger()->addError($this->t('Error sending email message.'));
      }
    }
  }
}