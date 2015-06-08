<?php

final class PholioMockSearchEngine extends PhabricatorApplicationSearchEngine {

  public function getResultTypeDescription() {
    return pht('Pholio Mocks');
  }

  public function getApplicationClassName() {
    return 'PhabricatorPholioApplication';
  }

  public function newQuery() {
    return id(new PholioMockQuery())
      ->needCoverFiles(true)
      ->needImages(true)
      ->needTokenCounts(true);
  }

  protected function buildCustomSearchFields() {
    return array(
      id(new PhabricatorSearchUsersField())
        ->setKey('authorPHIDs')
        ->setAliases(array('authors'))
        ->setLabel(pht('Authors')),
      id(new PhabricatorSearchCheckboxesField())
        ->setKey('statuses')
        ->setLabel(pht('Status'))
        ->setOptions(
          id(new PholioMock())
            ->getStatuses()),
    );
  }

  public function buildQueryFromParameters(array $map) {
    $query = $this->newQuery();

    if ($map['authorPHIDs']) {
      $query->withAuthorPHIDs($map['authorPHIDs']);
    }

    if ($map['statuses']) {
      $query->withStatuses($map['statuses']);
    }

    return $query;
  }

  protected function getURI($path) {
    return '/pholio/'.$path;
  }

  protected function getBuiltinQueryNames() {
    $names = array(
      'open' => pht('Open Mocks'),
      'all' => pht('All Mocks'),
    );

    if ($this->requireViewer()->isLoggedIn()) {
      $names['authored'] = pht('Authored');
    }

    return $names;
  }

  public function buildSavedQueryFromBuiltin($query_key) {
    $query = $this->newSavedQuery();
    $query->setQueryKey($query_key);

    switch ($query_key) {
      case 'open':
        return $query->setParameter(
          'statuses',
          array('open'));
      case 'all':
        return $query;
      case 'authored':
        return $query->setParameter(
          'authorPHIDs',
          array($this->requireViewer()->getPHID()));
    }

    return parent::buildSavedQueryFromBuiltin($query_key);
  }

  protected function getRequiredHandlePHIDsForResultList(
    array $mocks,
    PhabricatorSavedQuery $query) {
    return mpull($mocks, 'getAuthorPHID');
  }

  protected function renderResultList(
    array $mocks,
    PhabricatorSavedQuery $query,
    array $handles) {
    assert_instances_of($mocks, 'PholioMock');

    $viewer = $this->requireViewer();

    $xform = PhabricatorFileTransform::getTransformByKey(
      PhabricatorFileThumbnailTransform::TRANSFORM_PINBOARD);

    $board = new PHUIPinboardView();
    foreach ($mocks as $mock) {

      $image = $mock->getCoverFile();
      $image_uri = $image->getURIForTransform($xform);
      list($x, $y) = $xform->getTransformedDimensions($image);

      $header = 'M'.$mock->getID().' '.$mock->getName();
      $item = id(new PHUIPinboardItemView())
        ->setHeader($header)
        ->setURI('/M'.$mock->getID())
        ->setImageURI($image_uri)
        ->setImageSize($x, $y)
        ->setDisabled($mock->isClosed())
        ->addIconCount('fa-picture-o', count($mock->getImages()))
        ->addIconCount('fa-trophy', $mock->getTokenCount());

      if ($mock->getAuthorPHID()) {
        $author_handle = $handles[$mock->getAuthorPHID()];
        $datetime = phabricator_date($mock->getDateCreated(), $viewer);
        $item->appendChild(
          pht('By %s on %s', $author_handle->renderLink(), $datetime));
      }

      $board->addItem($item);
    }

    return $board;
  }

}
