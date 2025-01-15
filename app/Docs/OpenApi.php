<?php

namespace App\Docs;

/**
 * @OA\Info(
 *     title="Play Create Eat API documentation",
 *     version="1.0.0",
 *     description="This is the official API documentation for the Play Create Eat application.",
 *     @OA\Contact(
 *         email="tech@playcreateeat.ae"
 *     ),
 *     )
 * @OA\SecurityScheme(
 * *     securityScheme="Sanctum",
 * *     type="http",
 * *     scheme="bearer",
 * *     bearerFormat="JWT",
 * *     description="Enter only your token. The 'Bearer ' prefix will be added automatically."
 * * )
 */
class OpenApi
{

}
