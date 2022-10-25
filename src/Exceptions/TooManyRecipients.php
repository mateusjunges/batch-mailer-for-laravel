<?php declare(strict_types=1);

namespace InteractionDesignFoundation\BatchMailer\Exceptions;

use InteractionDesignFoundation\BatchMailer\Contracts\Exception;

final class TooManyRecipients extends \Exception implements Exception
{

}