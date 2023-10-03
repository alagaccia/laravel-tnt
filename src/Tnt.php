<?php
namespace AndreaLagaccia\Tnt;

class Tnt
{
    protected $customer;
    protected $user;
    protected $password;
    protected $senderAccId;

    public function __construct()
    {
        $this->set_customer();
        $this->set_user();
        $this->set_password();
        $this->set_senderAccId();
    }

    public function set_customer()
    {
        $this->customer = config('tnt.CUSTOMER') ?? env('TNT_CUSTOMER');
    }

    public function set_user()
    {
        $this->user = config('tnt.USER') ?? env('TNT_USER');
    }

    public function set_password()
    {
        $this->password = config('tnt.PASSWORD') ?? env('TNT_PASSWORD');
    }

    public function set_senderAccId()
    {
        $this->senderAccId = config('tnt.SENDER_ACC_ID') ?? env('TNT_SENDER_ACC_ID');
    }

}
