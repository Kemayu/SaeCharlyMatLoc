<?php
declare(strict_types=1);

namespace charlymatloc\core\ports\api\dto;

class CredentialsDTO
{
    public string $email;
    public string $password;

    public function __construct(string $email, string $password)
    {
        $this->email = $email;
        $this->password = $password;
    }
}
