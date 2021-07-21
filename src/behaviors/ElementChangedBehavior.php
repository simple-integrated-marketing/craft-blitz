<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\blitz\behaviors;

use Craft;
use craft\base\Element;
use putyourlightson\blitz\helpers\ElementTypeHelper;
use yii\base\Behavior;

/**
 * This class attaches behavior to detect whether an element has changed.
 *
 * @since 3.6.0
 *
 * @property-read bool $hasChanged
 * @property-read bool $hasStatusChanged
 * @property-read bool $hasLiveOrExpiredStatus
 * @property Element $owner
 */
class ElementChangedBehavior extends Behavior
{
    // Constants
    // =========================================================================

    /**
     * @const string
     */
    const BEHAVIOR_NAME = 'elementChanged';

    // Properties
    // =========================================================================

    /**
     * @var string|null
     */
    public $previousStatus;

    /**
     * @var bool
     */
    public $deleted = false;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function attach($owner)
    {
        parent::attach($owner);

        $element = $this->owner;

        // Don't proceed if this is a new element
        if ($element->id === null) {
            return;
        }

        /** @var Element|null $originalElement */
        $originalElement = Craft::$app->getElements()->getElementById($element->id, get_class($element), $element->siteId);

        if ($originalElement !== null) {
            $this->previousStatus = $originalElement->getStatus();
        }

        $element->on(Element::EVENT_AFTER_DELETE, function() {
            $this->deleted = true;
        });
    }

    /**
     * Returns whether the element has changed.
     *
     * @return bool
     */
    public function getHasChanged(): bool
    {
        // If this is the canonical element then it must be newly created (not from a draft/revision).
        // https://craftcms.stackexchange.com/a/38046/180
        if ($this->owner->getIsCanonical()) {
            return true;
        }

        if ($this->deleted) {
            return true;
        }

        if ($this->getHasStatusChanged()) {
            return true;
        }

        if (!empty($this->owner->getDirtyAttributes()) || !empty($this->owner->getDirtyFields())
            || !empty($this->owner->getModifiedAttributes()) || !empty($this->owner->getModifiedFields())
        ) {
            return true;
        }

        return false;
    }

    /**
     * Returns whether the element's status has changed.
     *
     * @return bool
     */
    public function getHasStatusChanged(): bool
    {
        return $this->previousStatus === null || $this->previousStatus != $this->owner->getStatus();
    }

    /**
     * Returns whether the element has a live or expired status.
     *
     * @return bool
     */
    public function getHasLiveOrExpiredStatus(): bool
    {
        $elementStatus = $this->owner->getStatus();
        $liveStatus = ElementTypeHelper::getLiveStatus(get_class($this->owner));

        return ($elementStatus == $liveStatus || $elementStatus == 'expired');
    }
}
