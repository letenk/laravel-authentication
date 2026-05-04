<?php

namespace App\DTOs\RefreshToken;

class RefreshTokenRepositoryDTO
{
    public array $select;
    public array $filters;
    public array $eagerLoadRelation;

    public function __construct(array $params)
    {
        $this->select            = $params['select'] ?? ['*'];
        $this->filters           = $params['filters'] ?? [];
        $this->eagerLoadRelation = $params['eagerLoadRelation'] ?? [];
    }
}
