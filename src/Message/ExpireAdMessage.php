<?php

namespace App\Message;

final class ExpireAdMessage
{
    private int $adId;

    public function __construct(int $adId)
    {
        $this->adId = $adId;
    }

    public function getAdId(): int
    {
        return $this->adId;
    }
}
