<?php

final class PhabricatorProjectEditMainController
  extends PhabricatorProjectController {

  private $id;

  public function shouldAllowPublic() {
    // This page shows project history and some detailed information, and
    // it's reasonable to allow public access to it.
    return true;
  }

  public function willProcessRequest(array $data) {
    $this->id = idx($data, 'id');
  }

  public function processRequest() {
    $request = $this->getRequest();
    $viewer = $request->getUser();
    $id = $request->getURIData('id');

    $project = id(new PhabricatorProjectQuery())
      ->setViewer($viewer)
      ->withIDs(array($this->id))
      ->needImages(true)
      ->executeOne();
    if (!$project) {
      return new Aphront404Response();
    }

    $header = id(new PHUIHeaderView())
      ->setHeader(pht('Edit %s', $project->getName()))
      ->setUser($viewer)
      ->setPolicyObject($project);

    if ($project->getStatus() == PhabricatorProjectStatus::STATUS_ACTIVE) {
      $header->setStatus('fa-check', 'bluegrey', pht('Active'));
    } else {
      $header->setStatus('fa-ban', 'red', pht('Archived'));
    }

    $actions = $this->buildActionListView($project);
    $properties = $this->buildPropertyListView($project, $actions);

    $object_box = id(new PHUIObjectBoxView())
      ->setHeader($header)
      ->addPropertyList($properties);

    $timeline = $this->buildTransactionTimeline(
      $project,
      new PhabricatorProjectTransactionQuery());
    $timeline->setShouldTerminate(true);

    $nav = $this->buildIconNavView($project);
    $nav->selectFilter("edit/{$id}/");
    $nav->appendChild($object_box);
    $nav->appendChild($timeline);

    $mnav = $this->buildSideNavView();

    return $this->buildApplicationPage(
      array(
        $nav,
      ),
      array(
        'title' => $project->getName(),
      ));
  }

  private function buildActionListView(PhabricatorProject $project) {
    $request = $this->getRequest();
    $viewer = $request->getUser();

    $id = $project->getID();

    $view = id(new PhabricatorActionListView())
      ->setUser($viewer)
      ->setObjectURI($request->getRequestURI());

    $can_edit = PhabricatorPolicyFilter::hasCapability(
      $viewer,
      $project,
      PhabricatorPolicyCapability::CAN_EDIT);

    $view->addAction(
      id(new PhabricatorActionView())
        ->setName(pht('Edit Details'))
        ->setIcon('fa-pencil')
        ->setHref($this->getApplicationURI("details/{$id}/"))
        ->setDisabled(!$can_edit)
        ->setWorkflow(!$can_edit));

    $view->addAction(
      id(new PhabricatorActionView())
        ->setName(pht('Edit Picture'))
        ->setIcon('fa-picture-o')
        ->setHref($this->getApplicationURI("picture/{$id}/"))
        ->setDisabled(!$can_edit)
        ->setWorkflow(!$can_edit));

    if ($project->isArchived()) {
      $view->addAction(
        id(new PhabricatorActionView())
          ->setName(pht('Activate Project'))
          ->setIcon('fa-check')
          ->setHref($this->getApplicationURI("archive/{$id}/"))
          ->setDisabled(!$can_edit)
          ->setWorkflow(true));
    } else {
      $view->addAction(
        id(new PhabricatorActionView())
          ->setName(pht('Archive Project'))
          ->setIcon('fa-ban')
          ->setHref($this->getApplicationURI("archive/{$id}/"))
          ->setDisabled(!$can_edit)
          ->setWorkflow(true));
    }

    return $view;
  }

  private function buildPropertyListView(
    PhabricatorProject $project,
    PhabricatorActionListView $actions) {
    $request = $this->getRequest();
    $viewer = $request->getUser();

    $view = id(new PHUIPropertyListView())
      ->setUser($viewer)
      ->setObject($project)
      ->setActionList($actions);

    $descriptions = PhabricatorPolicyQuery::renderPolicyDescriptions(
      $viewer,
      $project);

    $this->loadHandles(array($project->getPHID()));

    $view->addProperty(
      pht('Looks Like'),
      $this->getHandle($project->getPHID())->renderTag());

    $view->addProperty(
      pht('Visible To'),
      $descriptions[PhabricatorPolicyCapability::CAN_VIEW]);

    $view->addProperty(
      pht('Editable By'),
      $descriptions[PhabricatorPolicyCapability::CAN_EDIT]);

    $view->addProperty(
      pht('Joinable By'),
      $descriptions[PhabricatorPolicyCapability::CAN_JOIN]);

    return $view;
  }


}
