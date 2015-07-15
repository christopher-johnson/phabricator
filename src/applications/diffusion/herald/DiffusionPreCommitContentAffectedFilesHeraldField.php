<?php

final class DiffusionPreCommitContentAffectedFilesHeraldField
  extends DiffusionPreCommitContentHeraldField {

  const FIELDCONST = 'diffusion.pre.commit.affected';

  public function getHeraldFieldName() {
    return pht('Affected files');
  }

  public function getHeraldFieldValue($object) {
    return $this->getAdapter()->getDiffContent('name');
  }

  protected function getHeraldFieldStandardConditions() {
    return self::STANDARD_TEXT_LIST;
  }

  public function getHeraldFieldValueType($condition) {
    return HeraldAdapter::VALUE_TEXT;
  }

}
