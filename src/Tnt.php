<?php
namespace AndreaLagaccia\Tnt;

class Tnt
{
    protected $customer;
    protected $user;
    protected $password;
    protected $senderAccId;

    public function __construct($credentials = null)
    {
        $this->set_credentials($credentials);
    }

    public function set_credentials($credentials)
    {
        $data = json_decode($credentials);

        $this->set_customer($data['customer']);
        $this->set_user($data['user']);
        $this->set_password($data['password']);
        $this->set_senderAccId($data['senderAccId']);
    }

    public function set_customer($param = null)
    {
        $this->customer = $param ?? config('tnt.CUSTOMER') ?? env('TNT_CUSTOMER');
    }

    public function set_user($param = null)
    {
        $this->user = $param ?? config('tnt.USER') ?? env('TNT_USER');
    }

    public function set_password($param = null)
    {
        $this->password = $param ?? config('tnt.PASSWORD') ?? env('TNT_PASSWORD');
    }

    public function set_senderAccId($param = null)
    {
        $this->senderAccId = $param ?? config('tnt.SENDER_ACC_ID') ?? env('TNT_SENDER_ACC_ID');
    }

}
