<?php

final class PhamePostEditEngine
  extends PhabricatorEditEngine {

  private $blog;

  const ENGINECONST = 'phame.post';

  public function getEngineName() {
    return pht('Blog Posts');
  }

  public function getSummaryHeader() {
    return pht('Configure Blog Post Forms');
  }

  public function getSummaryText() {
    return pht('Configure creation and editing blog posts in Phame.');
  }

  public function setBlog(PhameBlog $blog) {
    $this->blog = $blog;
    return $this;
  }

  public function getEngineApplicationClass() {
    return 'PhabricatorPhameApplication';
  }

  protected function newEditableObject() {
    $viewer = $this->getViewer();

    if ($this->blog) {
      $blog = $this->blog;
    } else {
      $blog = PhameBlog::initializeNewBlog($viewer);
    }

    return PhamePost::initializePost($viewer, $blog);
  }

  protected function newObjectQuery() {
    return new PhamePostQuery();
  }

  protected function getObjectCreateTitleText($object) {
    return pht('Create New Post');
  }

  protected function getObjectEditTitleText($object) {
    return pht('Edit %s', $object->getTitle());
  }

  protected function getObjectEditShortText($object) {
    return $object->getTitle();
  }

  protected function getObjectCreateShortText() {
    return pht('Create Post');
  }

  protected function getObjectViewURI($object) {
    return $object->getViewURI();
  }

  protected function buildCustomEditFields($object) {

    if ($this->blog) {
      $blog_title = pht('Blog: %s', $this->blog->getName());
    } else {
      $blog_title = pht('Sample Blog Title');
    }

    return array(
      id(new PhabricatorInstructionsEditField())
        ->setValue($blog_title),
      id(new PhabricatorTextEditField())
        ->setKey('title')
        ->setLabel(pht('Title'))
        ->setDescription(pht('Post title.'))
        ->setConduitDescription(pht('Retitle the post.'))
        ->setConduitTypeDescription(pht('New post title.'))
        ->setTransactionType(PhamePostTransaction::TYPE_TITLE)
        ->setValue($object->getTitle()),
      id(new PhabricatorSelectEditField())
        ->setKey('visibility')
        ->setLabel(pht('Visibility'))
        ->setDescription(pht('Post visibility.'))
        ->setConduitDescription(pht('Change post visibility.'))
        ->setConduitTypeDescription(pht('New post visibility constant.'))
        ->setTransactionType(PhamePostTransaction::TYPE_VISIBILITY)
        ->setValue($object->getVisibility())
        ->setOptions(PhameConstants::getPhamePostStatusMap()),
      id(new PhabricatorRemarkupEditField())
        ->setKey('body')
        ->setLabel(pht('Body'))
        ->setDescription(pht('Post body.'))
        ->setConduitDescription(pht('Change post body.'))
        ->setConduitTypeDescription(pht('New post body.'))
        ->setTransactionType(PhamePostTransaction::TYPE_BODY)
        ->setValue($object->getBody())
        ->setPreviewPanel(
          id(new PHUIRemarkupPreviewPanel())
            ->setHeader(pht('Blog Post'))
            ->setPreviewType(PHUIRemarkupPreviewPanel::DOCUMENT)),
    );
  }

}
