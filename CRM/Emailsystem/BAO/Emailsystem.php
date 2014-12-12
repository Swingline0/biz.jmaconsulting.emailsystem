<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
*/

/**
 *
 * @package CiviCRM_Hook
 * @copyright CiviCRM LLC (c) 2004-2013
 * $Id: $
 *
 */

class CRM_Emailsystem_BAO_Emailsystem extends CRM_Core_DAO {
  
  /**
   * reminderParams
   * @var array
   * @static
   */
  private static $reminderParams;
  
  /**
   * function to get 14 schedule reminder names
   *
   * @access public
   * return array 
   */
  static function getScheduleReminderNames() {
    if (empty(self::$reminderParams)) {
      $participantStatus = CRM_Event_PseudoConstant::participantStatus();
      $statusArray = array(
        'pending' => 'Registered',
        'enrolled_pending' => 'enrolled_pending',
        'enrolled_paid' => 'enrolled-paid',
        'enrolled' => 'enrolled',
      );
      foreach ($statusArray as $key => $value) {
        $$key = array_search($value, $participantStatus);
      }
      self::$reminderParams = array(
        'custom_schedule_reminder_1' => array(
          'Application Reminder', 
          array('10', 'day', 'after', 'event_registration_start_date', $pending, 66)
        ),
        'custom_schedule_reminder_2' => array(
          'Application Now Due', 
          array('14', 'day', 'after', 'event_registration_start_date', $pending, 67)
        ),
        'custom_schedule_reminder_3' => array(
          'Incomplete Application Notification', 
          array('16', 'day', 'after', 'event_registration_start_date', $pending, 68)
        ),
        'custom_schedule_reminder_4' => array(
          'Reminder �V Deposit Due', 
          array('14', 'day', 'after', 'participant_register_date', $enrolled_pending, 71)
        ),
        'custom_schedule_reminder_5' => array(
          'Reminder �V Deposit Past Due', 
          array('21', 'day', 'after', 'participant_register_date', $enrolled_pending, 72)
        ),
        'custom_schedule_reminder_6' => array(
          'Student has not paid Deposit (Admin Notification > 10 weeks)', 
          array('28', 'day', 'after', 'participant_register_date', $enrolled_pending, 81)
        ),
        'custom_schedule_reminder_7' => array(
          'Reminder �V Program Balance Past Due', 
          array('7', 'day', 'after', 'participant_register_date', $enrolled_pending, 74)
        ),
        'custom_schedule_reminder_8' => array(
          'Student has not paid Deposit (Admin Notification < 10 weeks)', 
          array('14', 'day', 'after', 'participant_register_date', $enrolled_pending, 82)
        ),
        'custom_schedule_reminder_9' => array(
          'Reminder �V Program Payment due Shortly', 
          array('12', 'week', 'after', 'event_start_date', $enrolled_paid, 76)
        ),
        'custom_schedule_reminder_10' => array(
          'Reminder �V Program Payment Due', 
          array('10', 'week', 'after', 'event_start_date', $enrolled_paid, 77)
        ),
        'custom_schedule_reminder_11' => array(
          'Student has not paid Deposit-Admin Notification 7 days past du', 
          array('9', 'week', 'after', 'event_start_date', $enrolled_paid, 83)
        ),
        'custom_schedule_reminder_12' => array(
          'Course Roster and Health Statements', 
          array('2', 'week', 'after', 'event_start_date', '', 78)
        ),
        'custom_schedule_reminder_13' => array(
          'AMGA Program Evaluation', 
          array('1', 'day', 'before', 'event_end_date', $enrolled, 79)
        ),
        'custom_schedule_reminder_14' => array(
          'Student Evaluation Reminder for Instructors', 
          array('1', 'day', 'before', 'event_end_date', 1, 84)
        ),                            
      );
    }
    return self::$reminderParams;
  }
  
  /**
   * function to build where clause 
   *
   * @param string $scheduleReminderName name of schedule reminder
   *
   * @access public
   * return string 
   */
  static function getAdditionalWhereClause($scheduleReminderName) {
    $additionalWhereClause = '';
    switch ($scheduleReminderName) {
      case 'custom_schedule_reminder_4':
      case 'custom_schedule_reminder_5':
      case 'custom_schedule_reminder_6':
        // greater than 10 week
        $additionalWhereClause = ' r.start_date';
        break;
        
      case 'custom_schedule_reminder_7':
      case 'custom_schedule_reminder_8':
        // less than 10 week
        $additionalWhereClause = ' r.start_date ';
        break;
    }
    
    return $additionalWhereClause;
  }
  
  /**
   * function to build admin emails
   *
   * @param array $params array of params
   *
   * @param string $context
   *
   * @access public 
   */
  static function addCCToAdmin(&$params) {
    $scheduleReminderName = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_ActionSchedule', $params['entity_id']);
    switch ($scheduleReminderName) {
      // add cc to email
      case 'custom_schedule_reminder_2':
      case 'custom_schedule_reminder_3':
      case 'custom_schedule_reminder_5':
      case 'custom_schedule_reminder_7':
      case 'custom_schedule_reminder_10':
        $params['cc'] = self::getAdminEmails();
        break;
        
      // replace toEmail with admin
      case 'custom_schedule_reminder_6':
      case 'custom_schedule_reminder_8':
      case 'custom_schedule_reminder_11':
        $params['toName'] = '';
        $params['toEmail'] = self::getAdminEmails();
        break;

      // replace toEmail with instructor
      case 'custom_schedule_reminder_12':
      case 'custom_schedule_reminder_14':
        $params['toName'] = '';
        $params['toEmail'] = self::getAdminEmails('instructor');
        break;
    }
  }
  
  /**
   * function to get admin or instructor emails
   *
   * @param string $context
   *
   * @access public 
   * @return string 
   */
  static function getAdminEmails($context = 'admin') {
    $email = '';
    if ($context == 'admin') {
      // get all admin emails
      $email = 'pradeep.nayak@jmaconsulting.biz, pradpnayak@gmail.com';
    }
    else {
      // get all instructor emails
      $email = '';
    }
    
    return $email;
  }
  
  /**
   * This hook to alter query of schedule reminder to fetch recipients
   *
   * @param string $queryParams the params
   * @param object $scheduleReminder CRM_Core_BAO_ActionSchedule
   *
   * @access public
   */
  static function getReminderParameters($scheduleReminderName) {
    $params = array();
    
    $params = array(
      'start_action_offset',
      'start_action_unit',
      'start_action_condition',
      'start_action_date',
      'entity_status', 
      'msg_template_id' 
    );
    $scheduleReminders = CRM_Emailsystem_BAO_Emailsystem::getScheduleReminderNames();
    $params = array_combine($params, $scheduleReminders[$scheduleReminderName][1]);
    if (!empty($params['msg_template_id'])) {
      $messageTemplates = new CRM_Core_DAO_MessageTemplate();
      $messageTemplates->id = $params['msg_template_id'];
      if ($messageTemplates->find(TRUE)) {
        $params += array(
          'body_html' => $messageTemplates->msg_html,
          'body_text' => $messageTemplates->msg_text,
          'subject' => $messageTemplates->msg_subject,
        );
      }
    }
    return $params;
  }
}
