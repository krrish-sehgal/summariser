<?php

namespace App\Services;

use Google\Client;
use Google\Service\Gmail;


class GoogleService
{
    protected $client;
    protected $service;

    public function __construct()
    {
        $this->client = new Client();
        $this->service = new Gmail($this->client);

        $this->client->setApplicationName(config('google.application_name'));
        $this->client->setClientId(config('google.client_id'));
        $this->client->setClientSecret(config('google.client_secret'));
        $this->client->setRedirectUri(config('google.redirect_uri'));
        $this->client->setScopes(config('google.scopes'));
        $this->client->setAccessType(config('google.access_type'));
        $this->client->setApprovalPrompt(config('google.approval_prompt'));
    }

    public function getAuthUrl()
    {
        return $this->client->createAuthUrl();
    }

    public function authenticate($code)
    {
        return $this->client->fetchAccessTokenWithAuthCode($code);
    }

    public function setAccessToken($token)
    {
        $this->client->setAccessToken($token);
    }

    public function listMessages()
    {
        $user = 'me';
        $currentDate = date('Y/m/d');
        $query = "is:unread in:inbox after:{$currentDate}";
        $response = $this->service->users_messages->listUsersMessages($user,['q'=>$query]);
        return $response->getMessages();
    }
    public function getMessage($messageId)
    {
        $user = 'me';
        $message = $this->service->users_messages->get($user, $messageId);
        return $message;
    }

}