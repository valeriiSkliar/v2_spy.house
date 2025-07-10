<?php

namespace App\Contracts;

/**
 * Интерфейс для валидаторов креативов
 * 
 * Обеспечивает единообразный API для различных типов валидации креативов
 * и позволяет легко добавлять новые валидаторы в будущем
 *
 * @package App\Contracts
 * @author SeniorSoftwareEngineer
 */
interface CreativeValidatorInterface
{
    /**
     * Валидирует изображения креатива
     *
     * @param array $imageUrls Массив URL изображений для проверки
     * @return array Результат валидации с детализацией по каждому URL
     */
    public function validateImages(array $imageUrls): array;

    /**
     * Проверяет доступность одного изображения
     *
     * @param string $imageUrl URL изображения
     * @return bool true если изображение доступно для скачивания
     */
    public function isImageAccessible(string $imageUrl): bool;

    /**
     * Возвращает детальную информацию о проверке изображения
     *
     * @param string $imageUrl URL изображения
     * @return array Массив с информацией о размере, типе, доступности
     */
    public function getImageDetails(string $imageUrl): array;

    /**
     * Проверяет валидность креатива в целом
     *
     * @param array $creativeData Данные креатива
     * @return bool true если креатив валиден
     */
    public function isCreativeValid(array $creativeData): bool;
}
