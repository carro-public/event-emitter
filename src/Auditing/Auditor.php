<?php

namespace CarroPublic\EventEmitter\Auditing;

use OwenIt\Auditing\Auditor as BaseAuditor;
use OwenIt\Auditing\Contracts\Auditable;

class Auditor extends BaseAuditor
{

    /**
     * {@inheritdoc}
     */
    public function execute(Auditable $model): void
    {
        if (method_exists($model, 'getIsEmittedModel') && $model->getIsEmittedModel()) {
            return;
        }

        parent::execute($model);
    }
}