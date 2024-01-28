<?php declare(strict_types=1);

namespace Junges\BatchMailer\Exceptions;

use Junges\BatchMailer\Contracts\Exception;

final class MissingRequiredParameter extends \Exception implements Exception
{

}