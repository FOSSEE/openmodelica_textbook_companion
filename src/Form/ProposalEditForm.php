<?php

/**
 * @file
 * Contains \Drupal\textbook_companion\Form\ProposalEditForm.
 */

namespace Drupal\textbook_companion\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\user\Entity\User;
use Symfony\Component\HttpFoundation\Response;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\DependencyInjection\ContainerInterface;
use Drupal\Core\Session\AccountProxy;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageInterface;



class ProposalEditForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'proposal_edit_form';
  }

  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state, $nonaicte_book = NULL) {
    $user = \Drupal::currentUser();
    /* get current proposal */
    $route_match = \Drupal::routeMatch();

$proposal_id = (int) $route_match->getParameter('id');
    /*$proposal_q = db_query("SELECT * FROM {textbook_companion_proposal} WHERE id = %d", $proposal_id);*/
    $query = \Drupal::database()->select('textbook_companion_proposal');
    $query->fields('textbook_companion_proposal');
    $query->condition('id', $proposal_id);
    $proposal_q = $query->execute();
    if ($proposal_q) {
      $proposal_data = $proposal_q->fetchObject();
      if (!$proposal_data) {
        \Drupal::messenger()->addError(t('Invalid proposal selected. Please try again.'));
        $response = new RedirectResponse(Url::fromRoute('textbook_companion._proposal_pending')->toString());
      $response->send();
        return;
      }
    }
    else {
      \Drupal::messenger()->addError(t('Invalid proposal selected. Please try again.'));
      $response = new RedirectResponse(Url::fromRoute('textbook_companion._proposal_pending')->toString());
      $response->send();
      return;
    }
    $user_data = \Drupal::entityTypeManager()->getStorage('user')->load($proposal_data->uid);
    /* $preference1_q = db_query("SELECT * FROM {textbook_companion_preference} WHERE proposal_id = %d AND pref_number = %d LIMIT 1", $proposal_id, 1);
    $preference1_data = db_fetch_object($preference1_q);*/
    $query = \Drupal::database()->select('textbook_companion_preference');
    $query->fields('textbook_companion_preference');
    $query->condition('proposal_id', $proposal_id);
    //$query->condition('pref_number', 1);
    $query->range(0, 1);
    $preference1_q = $query->execute();
    $preference1_data = $preference1_q->fetchObject();
      
    /*************************************************************************/
    $form['full_name'] = [
      '#type' => 'textfield',
      '#title' => t('Full Name'),
      //'#size' => 30,
      '#maxlength' => 50,
      '#required' => TRUE,
      '#default_value' => $proposal_data->full_name,
    ];
    $form['email_id'] = [
      '#type' => 'textfield',
      '#title' => t('Email'),
      //'#size' => 30,
      '#value' => $user ? $user->getEmail() : '',
      '#disabled' => TRUE,
    ];
    $form['mobile'] = [
      '#type' => 'textfield',
      '#title' => t('Mobile No.'),
      //'#size' => 30,
      '#maxlength' => 15,
      '#required' => TRUE,
      '#default_value' => $proposal_data->mobile,
    ];
    $form['how_project'] = [
      '#type' => 'select',
      '#title' => t('How did you come to know about this project'),
      '#options' => [
        'OpenModelica Website' => 'OpenModelica Website',
        'Friend' => 'Friend',
        'Professor/Teacher' => 'Professor/Teacher',
        'Mailing List' => 'Mailing List',
        'Poster in my/other college' => 'Poster in my/other college',
        'Others' => 'Others',
      ],
      '#required' => TRUE,
      '#default_value' => $proposal_data->how_project,
    ];
    $form['course'] = [
      '#type' => 'textfield',
      '#title' => t('Course'),
      //'#size' => 30,
      '#maxlength' => 50,
      '#required' => TRUE,
      '#default_value' => $proposal_data->course,
    ];
    $form['branch'] = [
      '#type' => 'select',
      '#title' => t('Department/Branch'),
      '#options' => _list_of_departments(),
      '#required' => TRUE,
      '#default_value' => $proposal_data->branch,
    ];
    $form['university'] = [
      '#type' => 'textfield',
      '#title' => t('University/ Institute'),
      //'#size' => 80,
      '#maxlength' => 200,
      '#required' => TRUE,
      '#attributes' => [
        'placeholder' => 'Insert full name of your institute/ university.... '
        ],
      '#default_value' => $proposal_data->university,
    ];
    $form['country'] = [
      '#type' => 'select',
      '#title' => t('Country'),
      '#options' => [
        'India' => 'India',
        'Others' => 'Others',
      ],
      '#required' => TRUE,
      '#tree' => TRUE,
      '#validated' => TRUE,
      '#default_value' => $proposal_data->country,
    ];
    $form['other_country'] = [
      '#type' => 'textfield',
      '#title' => t('Other than India'),
      //'#size' => 100,
      '#attributes' => [
        'placeholder' => t('Enter your country name')
        ],
      '#default_value' => $proposal_data->country,
      '#states' => [
        'visible' => [
          ':input[name="country"]' => [
            'value' => 'Others'
            ]
          ]
        ],
    ];
    $form['other_state'] = [
      '#type' => 'textfield',
      '#title' => t('State other than India'),
      //'#size' => 100,
      '#default_value' => $proposal_data->state,
      '#attributes' => [
        'placeholder' => t('Enter your state/region name')
        ],
      '#states' => [
        'visible' => [
          ':input[name="country"]' => [
            'value' => 'Others'
            ]
          ]
        ],
    ];
    $form['other_city'] = [
      '#type' => 'textfield',
      '#title' => t('City other than India'),
      //'#size' => 100,
      '#default_value' => $proposal_data->city,
      '#attributes' => [
        'placeholder' => t('Enter your city name')
        ],
      '#states' => [
        'visible' => [
          ':input[name="country"]' => [
            'value' => 'Others'
            ]
          ]
        ],
    ];
    $form['all_state'] = [
      '#type' => 'select',
      '#title' => t('State'),
      '#selected' => [
        '' => '-select-'
        ],
      '#options' => _list_of_states(),
      '#default_value' => $proposal_data->state,
      '#validated' => TRUE,
      '#states' => [
        'visible' => [
          ':input[name="country"]' => [
            'value' => 'India'
            ]
          ]
        ],
    ];
    $form['city'] = [
      '#type' => 'select',
      '#title' => t('City'),
      '#default_value' => $proposal_data->city,
      '#options' => _list_of_cities(),
      '#states' => [
        'visible' => [
          ':input[name="country"]' => [
            'value' => 'India'
            ]
          ]
        ],
    ];
    $form['pincode'] = [
      '#type' => 'textfield',
      '#title' => t('Pincode'),
      //'#size' => 30,
      '#maxlength' => 6,
      '#required' => FALSE,
      '#default_value' => $proposal_data->pincode,
      '#attributes' => [
        'placeholder' => 'Enter pincode....'
        ],
    ];
    /***************************************************************************/
    $form['hr'] = [
      '#type' => 'item',
      '#markup' => '<hr>',
    ];
    $form['faculty'] = [
      '#type' => 'hidden',
      '#title' => t('College Teacher/Professor'),
      //'#size' => 30,
      '#maxlength' => 100,
      '#required' => TRUE,
      '#default_value' => $proposal_data->faculty,
    ];
    $form['reviewer'] = [
      '#type' => 'hidden',
      '#title' => t('Reviewer'),
      //'#size' => 30,
      '#maxlength' => 100,
      '#default_value' => $proposal_data->reviewer,
    ];
    $form['completion_date'] = [
      '#type' => 'textfield',
      '#title' => t('Expected Date of Completion'),
      '#description' => t('Input date format should be DD-MM-YYYY. Eg: 23-03-2011'),
      //'#size' => 10,
      '#maxlength' => 10,
      '#default_value' => date('d-m-Y', $proposal_data->completion_date),
    ];
    $form['version'] = [
      '#type' => 'select',
      '#title' => t('OpenModelica Version'),
      //'#size' => 10,
      '#maxlength' => 100,
      '#options' => _list_of_software_version(),
      '#default_value' => $proposal_data->openmodelica_version,
    ];
    $form['other_version'] = [
      '#type' => 'textfield',
      //'#size' => 30,
      '#maxlength' => 50,
      //'#required' => TRUE,
		'#description' => t('Specify the other version used'),
      '#states' => [
        'visible' => [
          ':input[name="version"]' => [
            'value' => 'Other version'
            ]
          ]
        ],
    ];
    $form['operating_system'] = [
      '#type' => 'textfield',
      '#title' => t('Operating System'),
      //'#size' => 30,
      '#maxlength' => 50,
      '#default_value' => $proposal_data->operating_system,
    ];
    $form['preference1'] = [
      '#type' => 'fieldset',
      '#title' => t('Book Preference 1'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    ];
    $form['preference1']['book1'] = [
      '#type' => 'textfield',
      '#title' => t('Title of the book'),
      //'#size' => 30,
      '#maxlength' => 100,
      '#required' => TRUE,
      '#default_value' => $preference1_data->book,
    ];
    $form['preference1']['author1'] = [
      '#type' => 'textfield',
      '#title' => t('Author Name'),
      //'#size' => 30,
      '#maxlength' => 100,
      '#required' => TRUE,
      '#default_value' => $preference1_data->author,
    ];
    $form['preference1']['isbn1'] = [
      '#type' => 'textfield',
      '#title' => t('ISBN No'),
      //'#size' => 30,
      '#maxlength' => 25,
      '#required' => TRUE,
      '#default_value' => $preference1_data->isbn,
    ];
    $form['preference1']['publisher1'] = [
      '#type' => 'textfield',
      '#title' => t('Publisher & Place'),
      //'#size' => 30,
      '#maxlength' => 50,
      '#required' => TRUE,
      '#default_value' => $preference1_data->publisher,
    ];
    $form['preference1']['edition1'] = [
      '#type' => 'textfield',
      '#title' => t('Edition'),
      //'#size' => 4,
      '#maxlength' => 2,
      '#required' => TRUE,
      '#default_value' => $preference1_data->edition,
    ];
    $form['preference1']['year1'] = [
      '#type' => 'textfield',
      '#title' => t('Year of pulication'),
      //'#size' => 4,
      '#maxlength' => 4,
      '#required' => TRUE,
      '#default_value' => $preference1_data->year,
    ];
    
    /* hidden fields */
    $form['hidden_preference_id1'] = [
      '#type' => 'hidden',
      '#value' => $preference1_data->id,
    ];
    
    $form['hidden_proposal_id'] = [
      '#type' => 'hidden',
      '#value' => $proposal_id,
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => t('Submit'),
    ];
    // @FIXME
    // l() expects a Url object, created from a route name or external URI.
    $form['cancel'] = array(
            '#type' => 'item',
            '#markup' => Link::fromTextAndUrl('Cancel', Url::fromUri('internal:/textbook-companion/manage-proposal/all'))->toString()
        );

    return $form;
  }

  public function validateForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    if ($form_state->getValue(['book1']) && $form_state->getValue(['author1'])) {
      $bk1 = trim($form_state->getValue(['book1']));
      $auth1 = trim($form_state->getValue(['author1']));
      if (\Drupal::service('textbook_companion_global')->_dir_name($bk1, $auth1, $form_state->getValue([
        'hidden_preference_id1'
        ])) != NULL) {
        $form_state->setValue(['dir_name1'], _dir_name($bk1, $auth1, $form_state->getValue([
          'hidden_preference_id1'
          ])));
      }
    }
    /* mobile */
    if (!preg_match('/^[0-9\ \+]{0,15}$/', $form_state->getValue(['mobile']))) {
      $form_state->setErrorByName('mobile', t('Invalid mobile number'));
    }
    /* date of completion */
    if (!preg_match('/^[0-9]{1,2}-[0-9]{1,2}-[0-9]{4}$/', $form_state->getValue([
      'completion_date'
      ]))) {
      $form_state->setErrorByName('completion_date', t('Invalid expected date of completion'));
    }
    list($d, $m, $y) = explode('-', $form_state->getValue(['completion_date']));
    $d = (int) $d;
    $m = (int) $m;
    $y = (int) $y;
    if (!checkdate($m, $d, $y)) {
      $form_state->setErrorByName('completion_date', t('Invalid expected date of completion'));
    }
    //if (mktime(0, 0, 0, $m, $d, $y) <= time())
    //form_set_error('completion_date', t('Expected date of completion should be in future'));  
    /* edition */
    if (!preg_match('/^[1-9][0-9]{0,1}$/', $form_state->getValue(['edition1']))) {
      $form_state->setErrorByName('edition1', t('Invalid edition for Book Preference 1'));
    }
    /* year of publication */
    if (!preg_match('/^[1-3][0-9][0-9][0-9]$/', $form_state->getValue(['year1']))) {
      $form_state->setErrorByName('year1', t('Invalid year of pulication for Book Preference 1'));
    }
    /* year of publication */
    $cur_year = date('Y');
    if ((int) $form_state->getValue(['year1']) > $cur_year) {
      $form_state->setErrorByName('year1', t('Year of pulication should be not in the future for Book Preference 1'));
    }
    /* isbn */
    if (!preg_match('/^[0-9\-xX]+$/', $form_state->getValue(['isbn1']))) {
      $form_state->setErrorByName('isbn1', t('Invalid ISBN for Book Preference 1'));
    }
    if ($form_state->getValue(['version']) == 'Other Version') {
      if ($form_state->getValue(['other_version']) == '') {
        $form_state->setErrorByName('other_version', t('Please provide valid version'));
      }
    }
    return;
  }

  public function submitForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    /* completion date to timestamp */
    list($d, $m, $y) = explode('-', $form_state->getValue(['completion_date']));
    $completion_date_timestamp = mktime(0, 0, 0, $m, $d, $y);
    $proposal_id = $form_state->getValue(['hidden_proposal_id']);
    if ($form_state->getValue(['version']) == 'Other Version') {
      $form_state->setValue(['version'], $form_state->getValue(['other_version']));
    }
    if ($form_state->getValue(['country']) == 'other') {
      $form_state->setValue(['country'], $form_state->getValue(['other_country']));
      $form_state->setValue(['all_state'], $form_state->getValue(['other_state']));
    }
    $query = \Drupal::database()->update('textbook_companion_proposal');
    $query->fields([
      'full_name' => $form_state->getValue(['full_name']),
      'mobile' => $form_state->getValue(['mobile']),
      'how_project' => $form_state->getValue(['how_project']),
      'course' => $form_state->getValue(['course']),
      'branch' => $form_state->getValue(['branch']),
      'university' => $form_state->getValue(['university']),
      'city' => $form_state->getValue(['city']),
      'pincode' => $form_state->getValue(['pincode']),
      'state' => $form_state->getValue(['all_state']),
      'country' => $form_state->getValue(['country']),
      'faculty' => $form_state->getValue(['faculty']),
      'reviewer' => $form_state->getValue(['reviewer']),
      'completion_date' => $completion_date_timestamp,
      'operating_system' => $form_state->getValue(['operating_system']),
      'openmodelica_version' => $form_state->getValue(['version']),
    ]);
    $service = \Drupal::service('textbook_companion_global');
    $query->condition('id', $proposal_id);
    $num_updated = $query->execute();
    $query = \Drupal::database()->select('textbook_companion_preference');
    $query->fields('textbook_companion_preference');
    $query->condition('proposal_id', $proposal_id);
    $query->condition('pref_number', 1);
    $query->range(0, 1);
    $preference1_q = $query->execute();
    $preference1_data = $preference1_q->fetchObject();
    $preference1_id = $preference1_data->id;
    if ($preference1_data) {
      //del_book_pdf($preference1_data->id);
      $service->RenameDir($preference1_id, $form_state->getValue(['dir_name1']));
      $query = \Drupal::database()->update('textbook_companion_preference');
      $query->fields([
        'book' => $form_state->getValue(['book1']),
        'author' => $form_state->getValue(['author1']),
        'isbn' => $form_state->getValue(['isbn1']),
        'publisher' => $form_state->getValue(['publisher1']),
        'edition' => $form_state->getValue(['edition1']),
        'year' => $form_state->getValue(['year1']),
        'directory_name' => $form_state->getValue(['dir_name1']),
      ]);
      $query->condition('id', $preference1_id);
      $num_updated = $query->execute();
    }
    
    \Drupal::messenger()->addStatus(t('Proposal Updated'));
  }

}
?>
