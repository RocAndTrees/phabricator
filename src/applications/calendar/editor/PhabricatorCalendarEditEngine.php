<?php

final class PhabricatorCalendarEditEngine
  extends PhabricatorEditEngine {

  const ENGINECONST = 'calendar.event';

  public function getEngineName() {
    return pht('Calendar Events');
  }

  public function getSummaryHeader() {
    return pht('Configure Calendar Event Forms');
  }

  public function getSummaryText() {
    return pht('Configure how users create and edit events.');
  }

  public function getEngineApplicationClass() {
    return 'PhabricatorCalendarApplication';
  }

  protected function newEditableObject() {
    return PhabricatorCalendarEvent::initializeNewCalendarEvent(
      $this->getViewer(),
      $mode = null);
  }

  protected function newObjectQuery() {
    return new PhabricatorCalendarEventQuery();
  }

  protected function getObjectCreateTitleText($object) {
    return pht('Create New Event');
  }

  protected function getObjectEditTitleText($object) {
    return pht('Edit Event: %s', $object->getName());
  }

  protected function getObjectEditShortText($object) {
    return $object->getMonogram();
  }

  protected function getObjectCreateShortText() {
    return pht('Create Event');
  }

  protected function getObjectName() {
    return pht('Event');
  }

  protected function getObjectViewURI($object) {
    return $object->getURI();
  }

  protected function getEditorURI() {
    return $this->getApplication()->getApplicationURI('event/edit/');
  }

  protected function buildCustomEditFields($object) {
    $viewer = $this->getViewer();

    if ($this->getIsCreate()) {
      $invitee_phids = array($viewer->getPHID());
    } else {
      $invitee_phids = $object->getInviteePHIDsForEdit();
    }

    $frequency_options = array(
      PhabricatorCalendarEvent::FREQUENCY_DAILY => pht('Daily'),
      PhabricatorCalendarEvent::FREQUENCY_WEEKLY => pht('Weekly'),
      PhabricatorCalendarEvent::FREQUENCY_MONTHLY => pht('Monthly'),
      PhabricatorCalendarEvent::FREQUENCY_YEARLY => pht('Yearly'),
    );

    $fields = array(
      id(new PhabricatorTextEditField())
        ->setKey('name')
        ->setLabel(pht('Name'))
        ->setDescription(pht('Name of the event.'))
        ->setIsRequired(true)
        ->setTransactionType(PhabricatorCalendarEventTransaction::TYPE_NAME)
        ->setConduitDescription(pht('Rename the event.'))
        ->setConduitTypeDescription(pht('New event name.'))
        ->setValue($object->getName()),
      id(new PhabricatorRemarkupEditField())
        ->setKey('description')
        ->setLabel(pht('Description'))
        ->setDescription(pht('Description of the event.'))
        ->setTransactionType(
          PhabricatorCalendarEventTransaction::TYPE_DESCRIPTION)
        ->setConduitDescription(pht('Update the event description.'))
        ->setConduitTypeDescription(pht('New event description.'))
        ->setValue($object->getDescription()),
      id(new PhabricatorBoolEditField())
        ->setKey('cancelled')
        ->setOptions(pht('Active'), pht('Cancelled'))
        ->setLabel(pht('Cancelled'))
        ->setDescription(pht('Cancel the event.'))
        ->setTransactionType(
          PhabricatorCalendarEventTransaction::TYPE_CANCEL)
        ->setIsConduitOnly(true)
        ->setConduitDescription(pht('Cancel or restore the event.'))
        ->setConduitTypeDescription(pht('True to cancel the event.'))
        ->setValue($object->getIsCancelled()),
      id(new PhabricatorDatasourceEditField())
        ->setKey('inviteePHIDs')
        ->setAliases(array('invite', 'invitee', 'invitees', 'inviteePHID'))
        ->setLabel(pht('Invitees'))
        ->setDatasource(new PhabricatorMetaMTAMailableDatasource())
        ->setTransactionType(PhabricatorCalendarEventTransaction::TYPE_INVITE)
        ->setDescription(pht('Users invited to the event.'))
        ->setConduitDescription(pht('Change invited users.'))
        ->setConduitTypeDescription(pht('New event invitees.'))
        ->setValue($invitee_phids)
        ->setCommentActionLabel(pht('Change Invitees')),
    );

    if ($this->getIsCreate()) {
      $fields[] = id(new PhabricatorBoolEditField())
        ->setKey('isRecurring')
        ->setLabel(pht('Recurring'))
        ->setOptions(pht('One-Time Event'), pht('Recurring Event'))
        ->setTransactionType(
          PhabricatorCalendarEventTransaction::TYPE_RECURRING)
        ->setDescription(pht('One time or recurring event.'))
        ->setConduitDescription(pht('Make the event recurring.'))
        ->setConduitTypeDescription(pht('Mark the event as a recurring event.'))
        ->setValue($object->getIsRecurring());

      $fields[] = id(new PhabricatorSelectEditField())
        ->setKey('frequency')
        ->setLabel(pht('Frequency'))
        ->setOptions($frequency_options)
        ->setTransactionType(
          PhabricatorCalendarEventTransaction::TYPE_FREQUENCY)
        ->setDescription(pht('Recurring event frequency.'))
        ->setConduitDescription(pht('Change the event frequency.'))
        ->setConduitTypeDescription(pht('New event frequency.'))
        ->setValue($object->getFrequencyUnit());
    }

    if ($this->getIsCreate() || $object->getIsRecurring()) {
      $fields[] = id(new PhabricatorEpochEditField())
        ->setAllowNull(true)
        ->setKey('until')
        ->setLabel(pht('Repeat Until'))
        ->setTransactionType(
          PhabricatorCalendarEventTransaction::TYPE_RECURRENCE_END_DATE)
        ->setDescription(pht('Last instance of the event.'))
        ->setConduitDescription(pht('Change when the event repeats until.'))
        ->setConduitTypeDescription(pht('New final event time.'))
        ->setValue($object->getRecurrenceEndDate());
    }

    $fields[] = id(new PhabricatorBoolEditField())
        ->setKey('isAllDay')
        ->setLabel(pht('All Day'))
        ->setOptions(pht('Normal Event'), pht('All Day Event'))
        ->setTransactionType(PhabricatorCalendarEventTransaction::TYPE_ALL_DAY)
        ->setDescription(pht('Marks this as an all day event.'))
        ->setConduitDescription(pht('Make the event an all day event.'))
        ->setConduitTypeDescription(pht('Mark the event as an all day event.'))
        ->setValue($object->getIsAllDay());

    $fields[] = id(new PhabricatorEpochEditField())
        ->setKey('start')
        ->setLabel(pht('Start'))
        ->setTransactionType(
          PhabricatorCalendarEventTransaction::TYPE_START_DATE)
        ->setDescription(pht('Start time of the event.'))
        ->setConduitDescription(pht('Change the start time of the event.'))
        ->setConduitTypeDescription(pht('New event start time.'))
        ->setValue($object->getViewerDateFrom());

    $fields[] = id(new PhabricatorEpochEditField())
        ->setKey('end')
        ->setLabel(pht('End'))
        ->setTransactionType(
          PhabricatorCalendarEventTransaction::TYPE_END_DATE)
        ->setDescription(pht('End time of the event.'))
        ->setConduitDescription(pht('Change the end time of the event.'))
        ->setConduitTypeDescription(pht('New event end time.'))
        ->setValue($object->getViewerDateTo());

    $fields[] = id(new PhabricatorIconSetEditField())
        ->setKey('icon')
        ->setLabel(pht('Icon'))
        ->setIconSet(new PhabricatorCalendarIconSet())
        ->setTransactionType(PhabricatorCalendarEventTransaction::TYPE_ICON)
        ->setDescription(pht('Event icon.'))
        ->setConduitDescription(pht('Change the event icon.'))
        ->setConduitTypeDescription(pht('New event icon.'))
        ->setValue($object->getIcon());

    return $fields;
  }

}