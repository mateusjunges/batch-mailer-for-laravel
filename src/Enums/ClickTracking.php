<?php

namespace InteractionDesignFoundation\BatchMailer\Enums;

enum ClickTracking: int
{
    case HTML_ONLY = 1;
    case HTML_AND_TEXT = 2;
    case TEXT_ONLY = 3;
    CASE NONE = 4;
}
