<?php

namespace App\DTO;

use App\Models\Sport;
use App\Models\TeamType;

readonly class ValidatedTeamData
{
    public function __construct(
        public string $organization,
        public string $designation,
        public ?string $mascot,
        public TeamType $type,
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
            organization: (string) $data['organization'],
            designation: (string) $data['designation'],
            mascot: $data['mascot'] ?? null,
            type: TeamType::from($data['type']),
            sports: $sports,
        );
    }
}