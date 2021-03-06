<?php

namespace Tailgate\Application\Command\Season;

class UpdateSeasonCommand
{
    private $seasonId;
    private $sport;
    private $seasonType;
    private $name;
    private $seasonStart;
    private $seasonEnd;

    public function __construct($seasonId, $name, $sport, $seasonType, $seasonStart, $seasonEnd)
    {
        $this->name = $name;
        $this->seasonId = $seasonId;
        $this->sport = $sport;
        $this->seasonType = $seasonType;
        $this->seasonStart = $seasonStart;
        $this->seasonEnd = $seasonEnd;
    }

    public function getSeasonId()
    {
        return $this->seasonId;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getSport()
    {
        return $this->sport;
    }

    public function getSeasonType()
    {
        return $this->seasonType;
    }

    public function getSeasonStart()
    {
        return $this->seasonStart;
    }

    public function getSeasonEnd()
    {
        return $this->seasonEnd;
    }
}
