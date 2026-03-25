<?php

namespace App\Repositories\Eloquent;

use App\Models\PaymentTransaction;
use App\Repositories\Contracts\PaymentTransactionRepositoryInterface;

class PaymentTransactionRepository extends AbstractRepository implements PaymentTransactionRepositoryInterface
{
    public function __construct(PaymentTransaction $model)
    {
        parent::__construct($model);
    }
}
