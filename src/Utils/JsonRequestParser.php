<?php

declare(strict_types=1);

namespace App\Utils;

/**
 * Трейт для парсинга JSON из тела запроса.
 */
trait JsonRequestParser
{
    /**
     * Парсит JSON из php://input.
     *
     * @return array Распарсенные данные или пустой массив
     */
    protected function parseJsonInput(): array
    {
        // START_CONTRACT_parseJsonInput
        // Intent: Извлечь и распарсить JSON из тела HTTP-запроса
        // Input: php://input (неявно)
        // Output: array - распарсенные данные или []
        // END_CONTRACT_parseJsonInput
        $content = file_get_contents('php://input');
        if ($content === false) {
            return [];
        }
        
        $data = json_decode($content, true);
        return is_array($data) ? $data : [];
    }
}
