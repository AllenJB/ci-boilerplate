<?php

class File
{

    public function countLines($fh, $lineEnding = "\n")
    {
        $pointerPosition = ftell($fh);
        $lines = 0;
        while (! feof($fh)) {
            $lines += substr_count(fread($fh, 8192), $lineEnding);
        }
        fseek($fh, $pointerPosition);

        return $lines;
    }
}
