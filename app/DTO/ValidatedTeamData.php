<?php

namespace App\DTO;

use App\Models\Sport;

readonly class ValidatedTeamData
{
    public function __construct(
        public string $designation,
        public string $mascot,
        public array $sports,
    ) {}

    public static function fromArray(array $data): self
    {   
        // convert sports values to Sport enum instances
        $sports = [];
        if (isset($data['sports']) && is_array($data['sports'])) {
            $sports = array_map(function ($sport) {
                return $sport instanceof Sport ? $sport : Sport::from($sport);
            }, $data['sports']);
        }

        return new self(
            designation: (string) $data['designation'],
            mascot: (string) $data['mascot'],
            sports: $sports,
        );
    }
}