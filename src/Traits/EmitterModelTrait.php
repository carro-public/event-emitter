<?php
namespace CarroPublic\EventEmitter\Traits;

trait EmitterModelTrait
{
    /**
     * Settter of isEmittedModel attribute to indicate a model as emitted model
     *
     * @return void
     */
    public function setIsEmittedModel(): void
    {
        $this->isEmittedModel = true;
    }

    /**
     * Getter of isEmittedModel attribute
     *
     * @return bool
     */
    public function getIsEmittedModel() : bool
    {
        return $this->isEmittedModel ?? false;
    }
}
