<?php
/**
 * Parse the Message Inbound For Emojis
 *
 * @param string  $txt
 * The received message, where to find the Emojis.
 *
 * @param boolean $span
 * If "true", the function will return an html tag
 * Example: <span class="emoji emoji-1F604"></span>
 * this will show the smiling face, require emojisprite.css and emojisprite.png
 *
 * Otherwise, if it is "false", return an id. Example, ##1F604##.
 *
 * @return string
 */
function ParseMessageInboundForEmojis($txt, $span = true)
{
    $Emojis = ArrayEmojis();
    foreach ($Emojis as $Emoji) {
        $txt = str_replace(
            array($Emoji['iOS2'], $Emoji['iOS5'], $Emoji['iOS7']),
            (($span == true) ? '<span class="emoji emoji-' . $Emoji['Hex'] . '">&#35;&#35;' . $Emoji['Hex'] . '&#35;&#35;</span>' : '##' . $Emoji['Hex'] . '##'),
            $txt
        );
    }
    return $txt;
}

/**
 * This function extracts the phone number.
 *
 * @param string $from
 * The remitter delivered by WHATSAPP example 1234567890@s.whatsapp.net
 *
 * @return string
 * Returns the number of phone cleanly.
 *
 **/
function ExtractNumber($from)
{
    return str_replace(array("@s.whatsapp.net", "@g.us"), "", $from);
}

function wa_pbkdf2($algorithm, $password, $salt, $count, $key_length, $raw_output = false)
{
    $algorithm = strtolower($algorithm);
    if ( ! in_array($algorithm, hash_algos(), true)) {
        die('PBKDF2 ERROR: Invalid hash algorithm.');
    }
    if ($count <= 0 || $key_length <= 0) {
        die('PBKDF2 ERROR: Invalid parameters.');
    }

    $hash_length = strlen(hash($algorithm, "", true));
    $block_count = ceil($key_length / $hash_length);

    $output = "";
    for ($i = 1; $i <= $block_count; $i++) {
        $last = $salt . pack("N", $i);
        $last = $xorsum = hash_hmac($algorithm, $last, $password, true);
        for ($j = 1; $j < $count; $j++) {
            $xorsum ^= ($last = hash_hmac($algorithm, $last, $password, true));
        }
        $output .= $xorsum;
    }

    if ($raw_output) {
        return substr($output, 0, $key_length);
    } else {
        return bin2hex(substr($output, 0, $key_length));
    }
}

function preprocessProfilePicture($path)
{
    list($width, $height) = getimagesize($path);
    if ($width > $height) {
      $y = 0;
      $x = ($width - $height) / 2;
      $smallestSide = $height;
    } else {
      $x = 0;
      $y = ($height - $width) / 2;
      $smallestSide = $width;
    }

    $size = 639;
    $image = imagecreatetruecolor($size, $size);
    $img = imagecreatefromstring(file_get_contents($path));

    imagecopyresampled($image, $img, 0, 0, $x, $y, $size, $size, $smallestSide, $smallestSide);
    ob_start();
    imagejpeg($image);
    $i = ob_get_contents();
    ob_end_clean();

    imagedestroy($image);
    imagedestroy($img);

    return $i;
}

function createIcon($file)
{
    if ((extension_loaded('gd')) && (file_exists($file))) {
        return createIconGD($file);
    } else {
        return base64_decode(giftThumbnail());
    }
}

function createIconGD($file, $size = 100, $raw = true)
{
    list($width, $height) = getimagesize($file);
    if ($width > $height) {
        //landscape
        $nheight = ($height / $width) * $size;
        $nwidth  = $size;
    } else {
        $nwidth  = ($width / $height) * $size;
        $nheight = $size;
    }

    $image_p = imagecreatetruecolor($nwidth, $nheight);
    $image = imagecreatefromstring(file_get_contents($file));

    imagecopyresampled($image_p, $image, 0, 0, 0, 0, $nwidth, $nheight, $width, $height);
    ob_start();
    imagejpeg($image_p);
    $i = ob_get_contents();
    ob_end_clean();

    imagedestroy($image);
    imagedestroy($image_p);

    return $i;
}

function createVideoIcon($file)
{
    /* should install ffmpeg for the method to work successfully  */
    if (checkFFMPEG()) {
        //generate thumbnail
        $preview = sys_get_temp_dir() . '/' . md5($file) . '.jpg';
        @unlink($preview);

        //capture video preview
        $command = "ffmpeg -i \"" . $file . "\" -f mjpeg -ss 00:00:01 -vframes 1 \"" . $preview . "\"";
        exec($command);

        return createIconGD($preview);
    } else {
        return base64_decode(videoThumbnail());
    }
}

function checkFFMPEG()
{
    //check if ffmpeg is intalled
    $cmd = "ffmpeg -version";
    $res = exec($cmd, $output, $returnvalue);
    if ($returnvalue == 0) {
        return true;
    }
    return false;
}

function giftThumbnail()
{
    return '/9j/4AAQSkZJRgABAQEASABIAAD/4QCURXhpZgAASUkqAAgAAAADADEBAgAcAAAAMgAAADIBAgAUAAAATgAAAGmHBAABAAAAYgAAAAAAAABBZG9iZSBQaG90b3Nob3AgQ1MyIFdpbmRvd3MAMjAwNzoxMDoyMCAyMDo1NDo1OQADAAGgAwABAAAA//8SAAKgBAABAAAAvBIAAAOgBAABAAAAoA8AAAAAAAD/4gxYSUNDX1BST0ZJTEUAAQEAAAxITGlubwIQAABtbnRyUkdCIFhZWiAHzgACAAkABgAxAABhY3NwTVNGVAAAAABJRUMgc1JHQgAAAAAAAAAAAAAAAAAA9tYAAQAAAADTLUhQICAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAABFjcHJ0AAABUAAAADNkZXNjAAABhAAAAGx3dHB0AAAB8AAAABRia3B0AAACBAAAABRyWFlaAAACGAAAABRnWFlaAAACLAAAABRiWFlaAAACQAAAABRkbW5kAAACVAAAAHBkbWRkAAACxAAAAIh2dWVkAAADTAAAAIZ2aWV3AAAD1AAAACRsdW1pAAAD+AAAABRtZWFzAAAEDAAAACR0ZWNoAAAEMAAAAAxyVFJDAAAEPAAACAxnVFJDAAAEPAAACAxiVFJDAAAEPAAACAx0ZXh0AAAAAENvcHlyaWdodCAoYykgMTk5OCBIZXdsZXR0LVBhY2thcmQgQ29tcGFueQAAZGVzYwAAAAAAAAASc1JHQiBJRUM2MTk2Ni0yLjEAAAAAAAAAAAAAABJzUkdCIElFQzYxOTY2LTIuMQAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAWFlaIAAAAAAAAPNRAAEAAAABFsxYWVogAAAAAAAAAAAAAAAAAAAAAFhZWiAAAAAAAABvogAAOPUAAAOQWFlaIAAAAAAAAGKZAAC3hQAAGNpYWVogAAAAAAAAJKAAAA+EAAC2z2Rlc2MAAAAAAAAAFklFQyBodHRwOi8vd3d3LmllYy5jaAAAAAAAAAAAAAAAFklFQyBodHRwOi8vd3d3LmllYy5jaAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAABkZXNjAAAAAAAAAC5JRUMgNjE5NjYtMi4xIERlZmF1bHQgUkdCIGNvbG91ciBzcGFjZSAtIHNSR0IAAAAAAAAAAAAAAC5JRUMgNjE5NjYtMi4xIERlZmF1bHQgUkdCIGNvbG91ciBzcGFjZSAtIHNSR0IAAAAAAAAAAAAAAAAAAAAAAAAAAAAAZGVzYwAAAAAAAAAsUmVmZXJlbmNlIFZpZXdpbmcgQ29uZGl0aW9uIGluIElFQzYxOTY2LTIuMQAAAAAAAAAAAAAALFJlZmVyZW5jZSBWaWV3aW5nIENvbmRpdGlvbiBpbiBJRUM2MTk2Ni0yLjEAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAHZpZXcAAAAAABOk/gAUXy4AEM8UAAPtzAAEEwsAA1yeAAAAAVhZWiAAAAAAAEwJVgBQAAAAVx/nbWVhcwAAAAAAAAABAAAAAAAAAAAAAAAAAAAAAAAAAo8AAAACc2lnIAAAAABDUlQgY3VydgAAAAAAAAQAAAAABQAKAA8AFAAZAB4AIwAoAC0AMgA3ADsAQABFAEoATwBUAFkAXgBjAGgAbQByAHcAfACBAIYAiwCQAJUAmgCfAKQAqQCuALIAtwC8AMEAxgDLANAA1QDbAOAA5QDrAPAA9gD7AQEBBwENARMBGQEfASUBKwEyATgBPgFFAUwBUgFZAWABZwFuAXUBfAGDAYsBkgGaAaEBqQGxAbkBwQHJAdEB2QHhAekB8gH6AgMCDAIUAh0CJgIvAjgCQQJLAlQCXQJnAnECegKEAo4CmAKiAqwCtgLBAssC1QLgAusC9QMAAwsDFgMhAy0DOANDA08DWgNmA3IDfgOKA5YDogOuA7oDxwPTA+AD7AP5BAYEEwQgBC0EOwRIBFUEYwRxBH4EjASaBKgEtgTEBNME4QTwBP4FDQUcBSsFOgVJBVgFZwV3BYYFlgWmBbUFxQXVBeUF9gYGBhYGJwY3BkgGWQZqBnsGjAadBq8GwAbRBuMG9QcHBxkHKwc9B08HYQd0B4YHmQesB78H0gflB/gICwgfCDIIRghaCG4IggiWCKoIvgjSCOcI+wkQCSUJOglPCWQJeQmPCaQJugnPCeUJ+woRCicKPQpUCmoKgQqYCq4KxQrcCvMLCwsiCzkLUQtpC4ALmAuwC8gL4Qv5DBIMKgxDDFwMdQyODKcMwAzZDPMNDQ0mDUANWg10DY4NqQ3DDd4N+A4TDi4OSQ5kDn8Omw62DtIO7g8JDyUPQQ9eD3oPlg+zD88P7BAJECYQQxBhEH4QmxC5ENcQ9RETETERTxFtEYwRqhHJEegSBxImEkUSZBKEEqMSwxLjEwMTIxNDE2MTgxOkE8UT5RQGFCcUSRRqFIsUrRTOFPAVEhU0FVYVeBWbFb0V4BYDFiYWSRZsFo8WshbWFvoXHRdBF2UXiReuF9IX9xgbGEAYZRiKGK8Y1Rj6GSAZRRlrGZEZtxndGgQaKhpRGncanhrFGuwbFBs7G2MbihuyG9ocAhwqHFIcexyjHMwc9R0eHUcdcB2ZHcMd7B4WHkAeah6UHr4e6R8THz4faR+UH78f6iAVIEEgbCCYIMQg8CEcIUghdSGhIc4h+yInIlUigiKvIt0jCiM4I2YjlCPCI/AkHyRNJHwkqyTaJQklOCVoJZclxyX3JicmVyaHJrcm6CcYJ0kneierJ9woDSg/KHEooijUKQYpOClrKZ0p0CoCKjUqaCqbKs8rAis2K2krnSvRLAUsOSxuLKIs1y0MLUEtdi2rLeEuFi5MLoIuty7uLyQvWi+RL8cv/jA1MGwwpDDbMRIxSjGCMbox8jIqMmMymzLUMw0zRjN/M7gz8TQrNGU0njTYNRM1TTWHNcI1/TY3NnI2rjbpNyQ3YDecN9c4FDhQOIw4yDkFOUI5fzm8Ofk6Njp0OrI67zstO2s7qjvoPCc8ZTykPOM9Ij1hPaE94D4gPmA+oD7gPyE/YT+iP+JAI0BkQKZA50EpQWpBrEHuQjBCckK1QvdDOkN9Q8BEA0RHRIpEzkUSRVVFmkXeRiJGZ0arRvBHNUd7R8BIBUhLSJFI10kdSWNJqUnwSjdKfUrESwxLU0uaS+JMKkxyTLpNAk1KTZNN3E4lTm5Ot08AT0lPk0/dUCdQcVC7UQZRUFGbUeZSMVJ8UsdTE1NfU6pT9lRCVI9U21UoVXVVwlYPVlxWqVb3V0RXklfgWC9YfVjLWRpZaVm4WgdaVlqmWvVbRVuVW+VcNVyGXNZdJ114XcleGl5sXr1fD19hX7NgBWBXYKpg/GFPYaJh9WJJYpxi8GNDY5dj62RAZJRk6WU9ZZJl52Y9ZpJm6Gc9Z5Nn6Wg/aJZo7GlDaZpp8WpIap9q92tPa6dr/2xXbK9tCG1gbbluEm5rbsRvHm94b9FwK3CGcOBxOnGVcfByS3KmcwFzXXO4dBR0cHTMdSh1hXXhdj52m3b4d1Z3s3gReG54zHkqeYl553pGeqV7BHtje8J8IXyBfOF9QX2hfgF+Yn7CfyN/hH/lgEeAqIEKgWuBzYIwgpKC9INXg7qEHYSAhOOFR4Wrhg6GcobXhzuHn4gEiGmIzokziZmJ/opkisqLMIuWi/yMY4zKjTGNmI3/jmaOzo82j56QBpBukNaRP5GokhGSepLjk02TtpQglIqU9JVflcmWNJaflwqXdZfgmEyYuJkkmZCZ/JpomtWbQpuvnByciZz3nWSd0p5Anq6fHZ+Ln/qgaaDYoUehtqImopajBqN2o+akVqTHpTilqaYapoum/adup+CoUqjEqTepqaocqo+rAqt1q+msXKzQrUStuK4trqGvFq+LsACwdbDqsWCx1rJLssKzOLOutCW0nLUTtYq2AbZ5tvC3aLfguFm40blKucK6O7q1uy67p7whvJu9Fb2Pvgq+hL7/v3q/9cBwwOzBZ8Hjwl/C28NYw9TEUcTOxUvFyMZGxsPHQce/yD3IvMk6ybnKOMq3yzbLtsw1zLXNNc21zjbOts83z7jQOdC60TzRvtI/0sHTRNPG1EnUy9VO1dHWVdbY11zX4Nhk2OjZbNnx2nba+9uA3AXcit0Q3ZbeHN6i3ynfr+A24L3hROHM4lPi2+Nj4+vkc+T85YTmDeaW5x/nqegy6LzpRunQ6lvq5etw6/vshu0R7ZzuKO6070DvzPBY8OXxcvH/8ozzGfOn9DT0wvVQ9d72bfb794r4Gfio+Tj5x/pX+uf7d/wH/Jj9Kf26/kv+3P9t////2wBDAAEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQH/2wBDAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQH/wAARCABTAGQDASIAAhEBAxEB/8QAHQABAAICAwEBAAAAAAAAAAAAAAgKBgkBBQcLBP/EADsQAAAGAQIEBAQEBAQHAAAAAAECAwQFBgcAEQgJEiETFDFBFSJRYQojMnEWJYGRFyRCUjNDYpKxwdH/xAAbAQEAAwEBAQEAAAAAAAAAAAAABQYHCAQDCf/EADMRAAICAQMEAAUCAwkBAAAAAAECAwQFAAYRBxITIQgUFSIxQVEjQmEkMnGBgpGUofDB/9oADAMBAAIRAxEAPwC/xpprgRAoCIjsAAIiI9gAADcREfQAAPcdNNc64EdvX7B/UR2AP6j215RbM44rpfiJzdxiheJgbeNijqTkn1l3/LMyiE3iqJxENv8AM+AQB/UcvrrTtzG+O3ivjMYu3vAq6xrj59XIe1WG73XOdPfWGSGMhYssmyNQoqNk30DFOGaTKWdybu6w04k7AI9s0jGgkeLKfGebwQyTGOSURr3GOIKZGH69od0T0PZ7nUcA++fWpDFY/wCqZCpjxbp0DblES277TpUhYglTM1avan4dgI18VeVi7KO3jkjDeJr8Shy/MDZDyPhyinyJxDZTxbYpao26KxxERUPUIeywL1eKmY5zd7nKRCUk1jpZsvGOZepQdoixdoqkbunAFAxpD8uHnOcP/MBi7gzeQLrh9yHSlEF31QvtrhpODm4V4YxW8nUryDWvspl0zEEwnYJ1ExUrF+aauW6UnGKKP0fl4rNImeucrkVSbl2N4ss9I2iwyscdig0mZiekXMvLeahU2hWCUc/evnImimhW7RNNRNNIpBQQMnkVZ4qf4Cv3gUC+S9LtsFJonYzrJ8rCsl5FmYoh5J158ia4JKmUb9MoiDV6TxkRA6CokVxI9QNx2M6k2KqS5DCwR+TI41scILNeEMqSSraV5u/gHyV2WQc8Ok9cKnlP6ixfCB0XxPSq1j9/56js/qblrYp7L3nFvP6rhMvkWryW6dGTAzQY0VgxX5PMxSVpAoavaxOXM1gUE+uVxbcY+HODzhwv/EvkWaQk6fSo9H4fGV2QjXkvdLPKrAyrFNrO7ryrmasMkom3RMdTy8eyI+mX5k4yMeLJ1wqB+LOw0qms5zLwlZCpUazK6dPZXH+T6nfE2zBDrVO5VZWqDxsYvgIFAVeiQOVRQBKgAmOmmamfxIcwbJ+aEa7HZ2ye0l4itI+aja1X4ljFxakkZE7daxLwVXRSi31keImO1+JOkiC3bGVbsvINl3BFo4Y7udBzJJNq9bJOIqtRcvSupc8vYm0I7cxcccHRECOXiHwxR8qZIV0Yh06bt3jsjBAzg5vlD25Td+679+K1gKN6ngIEjE0s9Cq890t98phSyeGkVVMcEVeft5++Zx5FVKtsT4dvh/2ttO9gerW6dr7k6uZWxZbG0sTurO1sVtqONUgoR5CzhVLRU5ppo7eUv5bFeRY3atjq7GlNPY+xDwn8UWJeNDh6xjxNYOlJGWxjliDcTdbXmY4YiaaiwlpGAmYibixWcgwmIOdiZKJk2ybly3K6ZnO1dOWqiDhWROqLvJ74vXfCnlTHfDXhe03TJuJpllO/EcSvrbLWKrU9g+ayFrcXaPICDuvYwUCZVO9lPhTFiWwupddnIs30i5bOW9u2tcXNTlkyDOVO0wCh9tzIEYTjQm+24io0cNnogG/tH9Q7fp37a0Dae5It0YoZGKrbqdkzVpEtxiMyPHHG5mhKko8MgkHBHBVw6EfaC3IfxAdFr3Qrf0mzruf2/uEWMdBmatnAXXtpUr27NyuuNyCTJHPXyFVqj96OrLNXkrWUf+O0cUtdNYLVMk026nMjXZfzrhNPxFmyrGRZLoh9FE3rRAAEfYAMbq79PUACIZ1qzaw/TTTTTTTWurjQslnrttqJG0iuesTFcdFWgnCi5oleTjJU3mHBkG6zY/mFWkg0TOoKpg8NEgdHbvsV1DLjUo72wY/j7gzKgdLH60nJzBTicHAQb5s3TdrtiESUFwdq5atF1ENyG8v4yiYmMn4ZmmoFMLdU10fAko9zBHH/AJzMhZKO6hDuYyQFQfIgO/sk8EP94j31Xi5hnNroVOs+R8D4Vp89cbVWpOdotpslxBpW6ILtuC8XNNIuCEj6xWqOHrXaHUepVxs+QOcyRFmypVD7vyLt3jRRZqui6SEpigdFQigFMAehuncxDB/tMBTb9hL3DVJrmL10ILjZ4jGYpACTy/fG00zB1F6LBAwcwJ+4bbGUeKG2EBEB9x/Vqkb8yeTxeLry4uda0k9sV5JTDHOQrQyyABZQUHPjIJI5/bjXUXwn7D2Nv/f2Vx2+sRYzlLGbefMVMdDlLeKSWeHJ46o7zTUWjsuI0uqVjEqxk8+RJBwBAiLwRnvKWR/LYXqSFmm5d8m9jMeUerT0oVkUTl6CRkezUnJZpHEMXfxX6izJuAiJlkUigUuznh6/DicUOSF2MzmmDicSRz45XLqMelC43RQFT9ZyHYJGQhY5UeowiaQeO1iG/wCI06tyhY25JuSlqVwTUB3XYOoi6dz16Y2ldzWowspYHUbbpNJJWXsDNBrYXyiLNRu2bC8k3KLdBFNFugmmmUmt7lVz3jySAqFmgntYcqDsZ6z/AJ1EAJuxjABU0ZJsQd9tzIuugv8AqH114ttbbsHH1L1+6tixer1rMhpQJjY+yVBMkcy12VrDR+Q/ezKGJP8ADAPAsXXDrRiDu7P7U2hteXD4XauazOGpruTK2N5XBZoWzj7NzHS5lJocTHcNJCtaKGWSBFjPzjOnOqc2U/wycM3paD3EU3YntrimvW/i5aWRjDWECJgCqMe9aR4xkU69TNk/harEqogRYqpDCYdeVe5AWZ8rSVhh8JZQqKmQqWIK23DOYolShZVqA9YpovHBWjeSj5uvulfkjLlFEeVZ+JykUeNnQqMUvpZ1plUbWkVxV5eHnExAu3w10iqsmO3bxWo9DtEwb9wVQLsIDv2DUT+MTCvD7IwkTkC0uZurZkpk1AxmLsn4oli1nJFJttsnI6tRBW1nbpKoOYt2+kkBnqhJoTETYolF3HSkYo1VOJLLawyMAYVVuF7TGT4weBx9jDkI3A/mV0b2HTk+RcTwPUq7Xk8eSsyxKZfIttI/myvc/f2Wa8hDWa/P95Y5q9iIdrQTdkXystCiC4M+bFyxI+0XthVs4UOsNRCduF6rDGvZLxK4aRwFBxJ26QiSWmBi4to3ABM7szKHQaJiAiLfcxwsN8oTj0zXxLZkPhfPbfG178rjOx3k1mx7ASNScRa9fcQTVmxsMi3fqQFiCUWmSoOiVyvwyTNVMPCmnZjGQLvG4lOFviD4quCzJfD9acp1PH2R8mYwkMdTzqNry72hoSJnqTJ7Omb159HSTxvZIxj8QVj1SnCGdSyjNNA6TAET61eWxylc4cv7L2VMoZTteML5XJDFSNRrUjjp9Y1pYrl1bYeTlBfV6xV+JdMkhYxTREh2z+QKoqc5DdKSfi6p4wG4MXuPDPjslmJ8LO5kyVeeWu1WqE+4V/HGqqsTgdg7I+VcnibgjXRzdWOkG/ei3UqvvPY3TfE9UMXUjqbLzGLoZetnc885SE5Y3bVieae/WlbzyC1cEc8Cnvxq9snO+nG5TubMzRRSRaR0YzfOUY9kkVqxROchGhTg3J2UWHxx3crnWcnHfrWN33kXrxnDrA6sY9sDpqZo6duVWDZAywKmTj2wInAVugATBws5FQVAIZQhCJpEKcwgcxvZtafrhnTTTTTTTWI36GLYqPcIEyYKhMVidjQIIb7ndxjpFLt9QVMQQ+4BrLtcCG4beu/YQH3D39ftppquS8rqB+h02MsyeiQDGcszi3XAwgHUB/D2KoACAgJFSnKP021Vc5wWMWtV4mo+4uZR0Z3lKjRss4UWZpAxLIVFQtTWL1NdlSHcsW0Y5VOKChAWMoP5YGDVu67Rn8PXO3QJy9PwazT8aUuwAJSNpR2kj6+n5IJGL7CAgP7VueefWygrw8WoCgPz5Frap9h2+dOszKCY+pQH8tybYR6h7iAbAIlp++4Vl23bkZAxqy1bCc8/a3zCQFhwR78c8g98j37H7dH/AAoZWxjutW3q0Nh66ZvH53FWCoQ+WNcVYysUTCRWBU3MXVf0A3KABgCeZDck3IVZkeGqfxuNhiz2ulZNtLh5AlcgEghD2YkdLQ8gkmcpCuWb9QX4JKtTLCVZuukuVFVMSjupAol7D3++2wh6iHYO3/3fvvqstyOysn9v4jIB4mRUhoXHswkQ4FN0CR7YI86hPUxTABkwExdhAQL331YzTZT0QQBjJAXrYm2zCUE7lMpA79KLncHiAbb7B4ipC7bAkIdtSG1Z/mNvYl+AO2nHAACT6rc1+STx7Pi5PHrk+tUzrzivo/WLqFT7y/l3HayfcyhfebSLMsoUegsbXzGn5PYqkknk695xUdQmQqqZFVRETSiYHMkodMxyeEqIlMJBKJibB3KO4D27a/BxtzTlJximMZILOIqu5Fp96lGDdQCrSJarYY6ZK2E59yCu4BkcqSi3yAoYgm6SB26TFFrRRvlc+KRkiyXTeKHAqDdSRbr9DVwIg3VbF6+sRD5SOEkPufb5tZnlSKmMh2FNVwwLHR6RgBu3E5VXx0g9TvFybkSMft/lWnUBA3BRwqPpYNZHqU9y5jnBpjktUdZRzZDYsLkCQlWNW/xBibFBISMlGNUZOVYBJIRUjDorxrN2go6O5kEWwdYAkuobcoSAr+VcY5coCluxlkGm5BqcyzepRdjqFhjp6HkFmiotnSDV6xWUTWXauiGbOUSj4zdwQ6KxCKEMUKZ3PMRTh7bwYUdAgIFaV/N9uVTTL0gAqr0KtNjbAIAH63QB2336u+4ba37crCsBU+ALhuaeGCastT5e2Kl6QKYVbXbrFMEVHbcBMqgu3N19hMXbcAHfetV83PPui/ghDF8tSx0ds2B3+bzSNWAjYE9naVnJHChh2fkg+tszHTDFYvoRtTqs2SyH1rcu8b+3kxLrWOO+nUost3XYmES2hOtjF+Jw0skTCb0EKju3O0Rt5aqxJdtjLJKuTdttxcrqqlH/ALDE9f76y7XXxTfykXHNttvLsWqIh/1JoEKb+5gER12GrLrE9NNNNNNNB7gIfXTTTTWkbinbowWeb63KIAR+5i5pIPTq+Kw7FdfYPfd2R0HV9d/pqvPzrIokpw/41sBC+Ieu5bQbmUAphFNKw1OdaDuIDsUDLx7cPm33MBekSjuA2NuZDXLJT7xC5WQr0xJUSSrraLstgimakg2qsnGO3XgOLAi0BR6ziXjN0UpZcrZZgyVQMWRWZpHSVNXk5oj9jb+DC2v2ayLssJaMfWVBZA6a6Z26ViQjDuUVUzGIdIzaYP8AnJmOmYg7huAiOoHc8Xm29l4/z/YpH9/vFxMD6/rGP01qvQ7I/Sur3Ty53doG6MbUZv2TJS/TJP8AeO4wP9DqDPJClPK8ReXYoTdISmIo14UOrYDGibe3KYRD36SP+wj6Bv31aVJ0CAB27AA77f179x+v/r121UT5N8+WN4x1Woq9prEVzZbbhsqZjJ16RKG3oIgVM+wevYfqOrb7VwU5CmD7bDuPoOw+3qH2H6D99RuxH7ttUlP5iktof+VMw/6P/verr8VdYQ9bt1TKOEuVduWVI/B523ioWI/1wuD7P4161iVsme9RJjFKIlRkTF2Dbv5BcN/cA237/wBtSXGDRVeiqJA2DcfQNtxEdgDf7eu3p376jnhzY12Y7AA7M5EQ7j6eUOACG/8AqDfv9v31LJQSE7mEAAA3HuAF2Dfv/wCe/wBP31byQByTwNc7AE/+/wDn51Uj57ckR5xnYYriRtyVHhmcyB0vdJa2ZNnF99v0gKratE222E3QHt06tK8JldCqYC4bqMCQJni8U4oh1UgAOy61ZhVHJdgKT5vHcrGPuUB36urc4m3qJ83edC5czSywaZ/EJDYz4faCmHUAlTWnCTk25IAduj5rMkdT5g6g2MIgUd9XauHrG0uuhAXCcK5i4CGj2bSnQaifguZRFkwTjmk/JpKF8VqwKgn4kIwOCbpwbw5V30JFZoqUTb48+7d32/X2HHVVI5/AgAZf8mg545/P6fnjq3q7J9L+Hf4cdv8APDXId7bhmT2PulzCvWdh+pMOWkCnjjtPr9NTG0001fNco6aaaaaaaaaaaa66VimUwyWYvkEnCCxDkMRUhVC7HKJTAJTAICUxREpiiAgYo7CAhquVzVOWAlf8G5cR4b26dZt9ph3KiuPyimjQ7RKIvW0skLdqcoI0+dcO2ZDJSkUCMY5WN/NGC3WLpGyNrp5aCjplEyL5AqgGKJd9gEdh+oCAgYPsIf115btc2a00AI4mikhcH8FJFKMP2B4Po/kfkfjU1t7KLhc1jMsVYyYy/TyNdkPDx2aViOzA/HoOoliUshPDD1+pB+WTwBxt5wPzAKNR8o1edoFwbsb1XZKuWdkrGyaajuAWcogikt+U+auDMBM1fMFXTJ0mHiN11ADfVv8ArU4R0miYD9e5QEA37+m24l9P329fUO2++3nLvAJw1ZvcM5DI2MqnY5qLIuWDsjyHbpWmvKrhsLmvWZqVGchHSfYyS8a+bnIcAOA77gOtXLfBDm7h6UdWDH5pnNuL2pjLKM2qIOcrVZkG4iZVigVFDIEe1T/UuwTZWoiROs7CdV6lNRWAxLYeoahkLos0siEgc9knae1iDwzBu4kgLzyPtHvV56tb+h6j7lh3GlUVZ2xFGjcRWYxyWKfkTzRLIPJHG8LRBYneZkZG/iuODr03C6nVcWxg2H+Wyg9/UDeCXbb9wN32H2EOwakZZ5VJi0cKnUTTKkkqooc5wIQhSFExjKHOJSkIUC7nMYQKQpRMO3tDnhwucPOS3xZpINl2rSLmiOlOoUTM12qaYO2r5FwCTiOdszAJXrJ6k2eNDlMRygkfcutkGK8NrW94zu99YmTr6ChHtYqb5ESnlVCnBVtPWNoqACVkQQIvDwbgnUsYE5KWJ2bMSTrqHXtPI/w1lyN2EHjnjg+/3GtBuEeU5lDig5juWuMriErslTsAQ2Q6DOYlrcwZJvN5iGgVOsM4GbeRwKnfwmPGkzFLPyoSiLKUtQkbIos0YJdZy6tfFDYAAR3H3H03H3Hb23H2DsHoHbXOwf39fvprwUMXVxz3JIAxlv2XtWZXILSSOSQPQACRg9ka/wAq/qWLM1v3XvnPbxr7cp5aWIUdqYSpgcJSroyQVKVaKJHfhmdns3JIxYtzseZJTwojhjhijaaaakdU7TTTTTTTTTTTTTTTTTTTTTTTTXkbnBGHnV7VyerjushfF0Ct31jQYA1dSpUVSKt1JtBqZFjOOmp0k/KPphq9eNCkBNsukn8uvXNNNNNNNNNNNNNNNNNNNNNNNf/Z';
}

function videoThumbnail()
{
    return '/9j/4QAYRXhpZgAASUkqAAgAAAAAAAAAAAAAAP/sABFEdWNreQABAAQAAABQAAD/4QMpaHR0cDovL25zLmFkb2JlLmNvbS94YXAvMS4wLwA8P3hwYWNrZXQgYmVnaW49Iu+7vyIgaWQ9Ilc1TTBNcENlaGlIenJlU3pOVGN6a2M5ZCI/PiA8eDp4bXBtZXRhIHhtbG5zOng9ImFkb2JlOm5zOm1ldGEvIiB4OnhtcHRrPSJBZG9iZSBYTVAgQ29yZSA1LjAtYzA2MCA2MS4xMzQ3NzcsIDIwMTAvMDIvMTItMTc6MzI6MDAgICAgICAgICI+IDxyZGY6UkRGIHhtbG5zOnJkZj0iaHR0cDovL3d3dy53My5vcmcvMTk5OS8wMi8yMi1yZGYtc3ludGF4LW5zIyI+IDxyZGY6RGVzY3JpcHRpb24gcmRmOmFib3V0PSIiIHhtbG5zOnhtcD0iaHR0cDovL25zLmFkb2JlLmNvbS94YXAvMS4wLyIgeG1sbnM6eG1wTU09Imh0dHA6Ly9ucy5hZG9iZS5jb20veGFwLzEuMC9tbS8iIHhtbG5zOnN0UmVmPSJodHRwOi8vbnMuYWRvYmUuY29tL3hhcC8xLjAvc1R5cGUvUmVzb3VyY2VSZWYjIiB4bXA6Q3JlYXRvclRvb2w9IkFkb2JlIFBob3Rvc2hvcCBDUzUgV2luZG93cyIgeG1wTU06SW5zdGFuY2VJRD0ieG1wLmlpZDo2MTQyRUVCOEI3MDgxMUUyQjNGQkY1OEU5M0U2MDE1MyIgeG1wTU06RG9jdW1lbnRJRD0ieG1wLmRpZDo2MTQyRUVCOUI3MDgxMUUyQjNGQkY1OEU5M0U2MDE1MyI+IDx4bXBNTTpEZXJpdmVkRnJvbSBzdFJlZjppbnN0YW5jZUlEPSJ4bXAuaWlkOjYxNDJFRUI2QjcwODExRTJCM0ZCRjU4RTkzRTYwMTUzIiBzdFJlZjpkb2N1bWVudElEPSJ4bXAuZGlkOjYxNDJFRUI3QjcwODExRTJCM0ZCRjU4RTkzRTYwMTUzIi8+IDwvcmRmOkRlc2NyaXB0aW9uPiA8L3JkZjpSREY+IDwveDp4bXBtZXRhPiA8P3hwYWNrZXQgZW5kPSJyIj8+/+4ADkFkb2JlAGTAAAAAAf/bAIQAAgICAgICAgICAgMCAgIDBAMCAgME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';
}
function updateData($nameFile, $WAver, $classesMD5 = "")
{
    $file    = __DIR__ . "/" . $nameFile;
    $open    = fopen($file, 'r+');
    $content = fread($open, filesize($file));
    fclose($open);

    $content = explode("\n", $content);

    if ($file == __DIR__ . '/token.php') {
        $content[6] = '    $classesMd5 = ' . "\"" . trim($classesMD5) . "\"; // $WAver";
    } else {
        if ($file == __DIR__ . '/whatsprot.class.php') {
            $content[48] = '    const WHATSAPP_VER = \'' . trim($WAver) . '\';                // The WhatsApp version.';
            $content[49] = '    const WHATSAPP_USER_AGENT = \'WhatsApp/' . trim($WAver) . ' Android/4.3 Device/GalaxyS3\'; // User agent used in request/registration code.';
        }
    }

    $content = implode("\n", $content);

    $open = fopen($file, 'w');
    fwrite($open, $content);
    fclose($open);
}

/**
 * This function generates a paymentLink where you can extend the account-expiration.
 *
 * @param string $number
 * Your number with international code, e.g. 49123456789
 * @param int    $sku
 * The Time in years (1, 3 or 5) you want to extend the account-expiration.
 *
 * @return string
 * Returns the link.
 *
 **/
function generatePaymentLink($number, $sku)
{
    if ($sku != 1 or $sku != 3 or $sku != 5) {
        $sku = 1;
    }
    $base     = "https://www.whatsapp.com/payments/cksum_pay.php?phone=";
    $middle   = "&cksum=";
    $end      = "&sku=" . $sku;
    $checksum = md5($number . "abc");
    $link     = $base . $number . $middle . $checksum . $end;
    return $link;
}

// Gets mime type of a file using various methods
function get_mime($file)
{
    if (function_exists("finfo_file")) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime  = finfo_file($finfo, $file);
        finfo_close($finfo);
        return $mime;
    } else {
        if (function_exists("mime_content_type")) {
            return mime_content_type($file);
        } else {
            if ( ! strncasecmp(PHP_OS, 'WIN', 3) == 0 && ! stristr(ini_get("disable_functions"), "shell_exec")) {
                $file = escapeshellarg($file);
                $mime = shell_exec("file -bi " . $file);
                return $mime;
            } else {
                return false;
            }
        }
    }
}

//Generate Array of Emojis iOS2, iOS5 and iOS7
function ArrayEmojis()
{
    return array(
        array(
            'iOS2' => '<img src=http://www.thyraz.info/emoji/001.png>',
            'iOS5' => '😄',
            'iOS7' => '',
            'Hex'  => '1F604'
        ),
        array(
            'iOS2' => '<img src=http://www.thyraz.info/emoji/003.png>',
            'iOS5' => '😃',
            'iOS7' => '',
            'Hex'  => '1F603'
        ),
        array('iOS2' => '', 'iOS5' => '😀', 'iOS7' => '', 'Hex' => '1F600'),
        array(
            'iOS2' => '<img src=http://www.thyraz.info/emoji/002.png>',
            'iOS5' => '😊',
            'iOS7' => '',
            'Hex'  => '1F60A'
        ),
        array(
            'iOS2' => '<img src=http://www.thyraz.info/emoji/004.png>',
            'iOS5' => '☺',
            'iOS7' => '☺️',
            'Hex'  => '263A'
        ),
        array(
            'iOS2' => '<img src=http://www.thyraz.info/emoji/005.png>',
            'iOS5' => '😉',
            'iOS7' => '',
            'Hex'  => '1F609'
        ),
        array(
            'iOS2' => '<img src=http://www.thyraz.info/emoji/006.png>',
            'iOS5' => '😍',
            'iOS7' => '',
            'Hex'  => '1F60D'
        ),
        array(
            'iOS2' => '<img src=http://www.thyraz.info/emoji/007.png>',
            'iOS5' => '😘',
            'iOS7' => '',
            'Hex'  => '1F618'
        ),
        array(
            'iOS2' => '<img src=http://www.thyraz.info/emoji/008.png>',
            'iOS5' => '😚',
            'iOS7' => '',
            'Hex'  => '1F61A'
        ),
        array('iOS2' => '', 'iOS5' => '😗', 'iOS7' => '', 'Hex' => '1F617'),
        array('iOS2' => '', 'iOS5' => '😙', 'iOS7' => '', 'Hex' => '1F619'),
        array(
            'iOS2' => '<img src=http://www.thyraz.info/emoji/012.png>',
            'iOS5' => '😜',
            'iOS7' => '',
            'Hex'  => '1F61C'
        ),
        array(
            'iOS2' => '<img src=http://www.thyraz.info/emoji/013.png>',
            'iOS5' => '😝',
            'iOS7' => '',
            'Hex'  => '1F61D'
        ),
        array('iOS2' => '', 'iOS5' => '😛', 'iOS7' => '', 'Hex' => '1F61B'),
        array(
            'iOS2' => '<img src=http://www.thyraz.info/emoji/009.png>',
            'iOS5' => '😳',
            'iOS7' => '',
            'Hex'  => '1F633'
        ),
        array(
            'iOS2' => '<img src=http://www.thyraz.info/emoji/011.png>',
            'iOS5' => '😁',
            'iOS7' => '',
            'Hex'  => '1F601'
        ),
        array(
            'iOS2' => '<img src=http://www.thyraz.info/emoji/017.png>',
            'iOS5' => '😔',
            'iOS7' => '',
            'Hex'  => '1F614'
        ),
        array(
            'iOS2' => '<img src=http://www.thyraz.info/emoji/010.png>',
            'iOS5' => '😌',
            'iOS7' => '',
            'Hex'  => '1F60C'
        ),
        array(
            'iOS2' => '<img src=http://www.thyraz.info/emoji/014.png>',
            'iOS5' => '😒',
            'iOS7' => '',
            'Hex'  => '1F612'
        ),
        array(
            'iOS2' => '<img src=http://www.thyraz.info/emoji/018.png>',
            'iOS5' => '😞',
            'iOS7' => '',
            'Hex'  => '1F61E'
        ),
        array(
            'iOS2' => '<img src=http://www.thyraz.info/emoji/023.png>',
            'iOS5' => '😣',
            'iOS7' => '',
            'Hex'  => '1F623'
        ),
        array(
            'iOS2' => '<img src=http://www.thyraz.info/emoji/024.png>',
            'iOS5' => '😢',
            'iOS7' => '',
            'Hex'  => '1F622'
        ),
        array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/026.png>',
			'iOS5' => '😂',
			'iOS7' => '',
			'Hex'  => '1F602'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/025.png>',
			'iOS5' => '😭',
			'iOS7' => '',
			'Hex'  => '1F62D'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/031.png>',
			'iOS5' => '😪',
			'iOS7' => '',
			'Hex'  => '1F62A'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/020.png>',
			'iOS5' => '😥',
			'iOS7' => '',
			'Hex'  => '1F625'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/021.png>',
			'iOS5' => '😰',
			'iOS7' => '',
			'Hex'  => '1F630'
		),
		array('iOS2' => '', 'iOS5' => '😅', 'iOS7' => '', 'Hex' => '1F605'),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/016.png>',
			'iOS5' => '😓',
			'iOS7' => '',
			'Hex'  => '1F613'
		),
		array('iOS2' => '', 'iOS5' => '😩', 'iOS7' => '', 'Hex' => '1F629'),
		array('iOS2' => '', 'iOS5' => '😫', 'iOS7' => '', 'Hex' => '1F62B'),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/022.png>',
			'iOS5' => '😨',
			'iOS7' => '',
			'Hex'  => '1F628'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/028.png>',
			'iOS5' => '😱',
			'iOS7' => '',
			'Hex'  => '1F631'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/029.png>',
			'iOS5' => '😠',
			'iOS7' => '',
			'Hex'  => '1F620'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/030.png>',
			'iOS5' => '😡',
			'iOS7' => '',
			'Hex'  => '1F621'
		),
		array('iOS2' => '', 'iOS5' => '😤', 'iOS7' => '', 'Hex' => '1F624'),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/019.png>',
			'iOS5' => '😖',
			'iOS7' => '',
			'Hex'  => '1F616'
		),
		array('iOS2' => '', 'iOS5' => '😆', 'iOS7' => '', 'Hex' => '1F606'),
		array('iOS2' => '', 'iOS5' => '😋', 'iOS7' => '', 'Hex' => '1F60B'),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/032.png>',
			'iOS5' => '😷',
			'iOS7' => '',
			'Hex'  => '1F637'
		),
		array('iOS2' => '', 'iOS5' => '😎', 'iOS7' => '', 'Hex' => '1F60E'),
		array('iOS2' => '', 'iOS5' => '😴', 'iOS7' => '', 'Hex' => '1F634'),
		array('iOS2' => '', 'iOS5' => '😵', 'iOS7' => '', 'Hex' => '1F635'),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/027.png>',
			'iOS5' => '😲',
			'iOS7' => '',
			'Hex'  => '1F632'
		),
		array('iOS2' => '', 'iOS5' => '😟', 'iOS7' => '', 'Hex' => '1F61F'),
		array('iOS2' => '', 'iOS5' => '😦', 'iOS7' => '', 'Hex' => '1F626'),
		array('iOS2' => '', 'iOS5' => '😧', 'iOS7' => '', 'Hex' => '1F627'),
		array('iOS2' => '', 'iOS5' => '😈', 'iOS7' => '', 'Hex' => '1F608'),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/033.png>',
			'iOS5' => '👿',
			'iOS7' => '',
			'Hex'  => '1F47F'
		),
		array('iOS2' => '', 'iOS5' => '😮', 'iOS7' => '', 'Hex' => '1F62E'),
		array('iOS2' => '', 'iOS5' => '😬', 'iOS7' => '', 'Hex' => '1F62C'),
		array('iOS2' => '', 'iOS5' => '😐', 'iOS7' => '', 'Hex' => '1F610'),
		array('iOS2' => '', 'iOS5' => '😕', 'iOS7' => '', 'Hex' => '1F615'),
		array('iOS2' => '', 'iOS5' => '😯', 'iOS7' => '', 'Hex' => '1F62F'),
		array('iOS2' => '', 'iOS5' => '😶', 'iOS7' => '', 'Hex' => '1F636'),
		array('iOS2' => '', 'iOS5' => '😇', 'iOS7' => '', 'Hex' => '1F607'),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/015.png>',
			'iOS5' => '😏',
			'iOS7' => '',
			'Hex'  => '1F60F'
		),
		array('iOS2' => '', 'iOS5' => '😑', 'iOS7' => '', 'Hex' => '1F611'),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/096.png>',
			'iOS5' => '👲',
			'iOS7' => '',
			'Hex'  => '1F472'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/097.png>',
			'iOS5' => '👳',
			'iOS7' => '',
			'Hex'  => '1F473'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/099.png>',
			'iOS5' => '👮',
			'iOS7' => '',
			'Hex'  => '1F46E'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/098.png>',
			'iOS5' => '👷',
			'iOS7' => '',
			'Hex'  => '1F477'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/102.png>',
			'iOS5' => '💂',
			'iOS7' => '',
			'Hex'  => '1F482'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/092.png>',
			'iOS5' => '👶',
			'iOS7' => '',
			'Hex'  => '1F476'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/088.png>',
			'iOS5' => '👦',
			'iOS7' => '',
			'Hex'  => '1F466'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/089.png>',
			'iOS5' => '👧',
			'iOS7' => '',
			'Hex'  => '1F467'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/091.png>',
			'iOS5' => '👨',
			'iOS7' => '',
			'Hex'  => '1F468'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/090.png>',
			'iOS5' => '👩',
			'iOS7' => '',
			'Hex'  => '1F469'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/094.png>',
			'iOS5' => '👴',
			'iOS7' => '',
			'Hex'  => '1F474'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/093.png>',
			'iOS5' => '👵',
			'iOS7' => '',
			'Hex'  => '1F475'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/095.png>',
			'iOS5' => '👱',
			'iOS7' => '',
			'Hex'  => '1F471'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/100.png>',
			'iOS5' => '👼',
			'iOS7' => '',
			'Hex'  => '1F47C'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/101.png>',
			'iOS5' => '👸',
			'iOS7' => '',
			'Hex'  => '1F478'
		),
		array('iOS2' => '', 'iOS5' => '😺', 'iOS7' => '', 'Hex' => '1F63A'),
		array('iOS2' => '', 'iOS5' => '😸', 'iOS7' => '', 'Hex' => '1F638'),
		array('iOS2' => '', 'iOS5' => '😻', 'iOS7' => '', 'Hex' => '1F63B'),
		array('iOS2' => '', 'iOS5' => '😽', 'iOS7' => '', 'Hex' => '1F63D'),
		array('iOS2' => '', 'iOS5' => '😼', 'iOS7' => '', 'Hex' => '1F63C'),
		array('iOS2' => '', 'iOS5' => '🙀', 'iOS7' => '', 'Hex' => '1F640'),
		array('iOS2' => '', 'iOS5' => '😿', 'iOS7' => '', 'Hex' => '1F63F'),
		array('iOS2' => '', 'iOS5' => '😹', 'iOS7' => '', 'Hex' => '1F639'),
		array('iOS2' => '', 'iOS5' => '😾', 'iOS7' => '', 'Hex' => '1F63E'),
		array('iOS2' => '', 'iOS5' => '👹', 'iOS7' => '', 'Hex' => '1F479'),
		array('iOS2' => '', 'iOS5' => '👺', 'iOS7' => '', 'Hex' => '1F47A'),
		array('iOS2' => '', 'iOS5' => '🙈', 'iOS7' => '', 'Hex' => '1F648'),
		array('iOS2' => '', 'iOS5' => '🙉', 'iOS7' => '', 'Hex' => '1F649'),
		array('iOS2' => '', 'iOS5' => '🙊', 'iOS7' => '', 'Hex' => '1F64A'),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/103.png>',
			'iOS5' => '💀',
			'iOS7' => '',
			'Hex'  => '1F480'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/034.png>',
			'iOS5' => '👽',
			'iOS7' => '',
			'Hex'  => '1F47D'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/055.png>',
			'iOS5' => '💩',
			'iOS7' => '',
			'Hex'  => '1F4A9'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/054.png>',
			'iOS5' => '🔥',
			'iOS7' => '',
			'Hex'  => '1F525'
		),
		array('iOS2' => '<img src=http://www.thyraz.info/emoji/044.png>', 'iOS5' => '✨', 'iOS7' => '', 'Hex' => '2728'),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/045.png>',
			'iOS5' => '🌟',
			'iOS7' => '',
			'Hex'  => '1F31F'
		),
		array('iOS2' => '', 'iOS5' => '💫', 'iOS7' => '', 'Hex' => '1F4AB'),
		array('iOS2' => '', 'iOS5' => '💥', 'iOS7' => '', 'Hex' => '1F4A5'),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/046.png>',
			'iOS5' => '💢',
			'iOS7' => '',
			'Hex'  => '1F4A2'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/051.png>',
			'iOS5' => '💦',
			'iOS7' => '',
			'Hex'  => '1F4A6'
		),
		array('iOS2' => '', 'iOS5' => '💧', 'iOS7' => '', 'Hex' => '1F4A7'),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/049.png>',
			'iOS5' => '💤',
			'iOS7' => '',
			'Hex'  => '1F4A4'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/050.png>',
			'iOS5' => '💨',
			'iOS7' => '',
			'Hex'  => '1F4A8'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/107.png>',
			'iOS5' => '👂',
			'iOS7' => '',
			'Hex'  => '1F442'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/108.png>',
			'iOS5' => '👀',
			'iOS7' => '',
			'Hex'  => '1F440'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/109.png>',
			'iOS5' => '👃',
			'iOS7' => '',
			'Hex'  => '1F443'
		),
		array('iOS2' => '', 'iOS5' => '👅', 'iOS7' => '', 'Hex' => '1F445'),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/106.png>',
			'iOS5' => '👄',
			'iOS7' => '',
			'Hex'  => '1F444'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/056.png>',
			'iOS5' => '👍',
			'iOS7' => '',
			'Hex'  => '1F44D'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/057.png>',
			'iOS5' => '👎',
			'iOS7' => '',
			'Hex'  => '1F44E'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/058.png>',
			'iOS5' => '👌',
			'iOS7' => '',
			'Hex'  => '1F44C'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/059.png>',
			'iOS5' => '👊',
			'iOS7' => '',
			'Hex'  => '1F44A'
		),
		array('iOS2' => '<img src=http://www.thyraz.info/emoji/060.png>', 'iOS5' => '✊', 'iOS7' => '', 'Hex' => '270A'),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/061.png>',
			'iOS5' => '✌',
			'iOS7' => '✌️',
			'Hex'  => '270C'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/062.png>',
			'iOS5' => '👋',
			'iOS7' => '',
			'Hex'  => '1F44B'
		),
		array('iOS2' => '<img src=http://www.thyraz.info/emoji/063.png>', 'iOS5' => '✋', 'iOS7' => '', 'Hex' => '270B'),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/064.png>',
			'iOS5' => '👐',
			'iOS7' => '',
			'Hex'  => '1F450'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/065.png>',
			'iOS5' => '👆',
			'iOS7' => '',
			'Hex'  => '1F446'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/066.png>',
			'iOS5' => '👇',
			'iOS7' => '',
			'Hex'  => '1F447'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/067.png>',
			'iOS5' => '👉',
			'iOS7' => '',
			'Hex'  => '1F449'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/068.png>',
			'iOS5' => '👈',
			'iOS7' => '',
			'Hex'  => '1F448'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/069.png>',
			'iOS5' => '🙌',
			'iOS7' => '',
			'Hex'  => '1F64C'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/070.png>',
			'iOS5' => '🙏',
			'iOS7' => '',
			'Hex'  => '1F64F'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/071.png>',
			'iOS5' => '☝',
			'iOS7' => '☝️',
			'Hex'  => '261D'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/072.png>',
			'iOS5' => '👏',
			'iOS7' => '',
			'Hex'  => '1F44F'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/073.png>',
			'iOS5' => '💪',
			'iOS7' => '',
			'Hex'  => '1F4AA'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/074.png>',
			'iOS5' => '🚶',
			'iOS7' => '',
			'Hex'  => '1F6B6'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/075.png>',
			'iOS5' => '🏃',
			'iOS7' => '',
			'Hex'  => '1F3C3'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/077.png>',
			'iOS5' => '💃',
			'iOS7' => '',
			'Hex'  => '1F483'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/076.png>',
			'iOS5' => '👫',
			'iOS7' => '',
			'Hex'  => '1F46B'
		),
		array('iOS2' => '', 'iOS5' => '👪', 'iOS7' => '', 'Hex' => '1F46A'),
		array('iOS2' => '', 'iOS5' => '👬', 'iOS7' => '', 'Hex' => '1F46C'),
		array('iOS2' => '', 'iOS5' => '👭', 'iOS7' => '', 'Hex' => '1F46D'),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/083.png>',
			'iOS5' => '💏',
			'iOS7' => '',
			'Hex'  => '1F48F'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/084.png>',
			'iOS5' => '💑',
			'iOS7' => '',
			'Hex'  => '1F491'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/078.png>',
			'iOS5' => '👯',
			'iOS7' => '',
			'Hex'  => '1F46F'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/079.png>',
			'iOS5' => '🙆',
			'iOS7' => '',
			'Hex'  => '1F646'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/080.png>',
			'iOS5' => '🙅',
			'iOS7' => '',
			'Hex'  => '1F645'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/081.png>',
			'iOS5' => '💁',
			'iOS7' => '',
			'Hex'  => '1F481'
		),
		array('iOS2' => '', 'iOS5' => '🙋', 'iOS7' => '', 'Hex' => '1F64B'),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/085.png>',
			'iOS5' => '💆',
			'iOS7' => '',
			'Hex'  => '1F486'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/086.png>',
			'iOS5' => '💇',
			'iOS7' => '',
			'Hex'  => '1F487'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/087.png>',
			'iOS5' => '💅',
			'iOS7' => '',
			'Hex'  => '1F485'
		),
		array('iOS2' => '', 'iOS5' => '👰', 'iOS7' => '', 'Hex' => '1F470'),
		array('iOS2' => '', 'iOS5' => '🙎', 'iOS7' => '', 'Hex' => '1F64E'),
		array('iOS2' => '', 'iOS5' => '🙍', 'iOS7' => '', 'Hex' => '1F64D'),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/082.png>',
			'iOS5' => '🙇',
			'iOS7' => '',
			'Hex'  => '1F647'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/257.png>',
			'iOS5' => '🎩',
			'iOS7' => '',
			'Hex'  => '1F3A9'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/258.png>',
			'iOS5' => '👑',
			'iOS7' => '',
			'Hex'  => '1F451'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/259.png>',
			'iOS5' => '👒',
			'iOS7' => '',
			'Hex'  => '1F452'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/247.png>',
			'iOS5' => '👟',
			'iOS7' => '',
			'Hex'  => '1F45F'
		),
		array('iOS2' => '', 'iOS5' => '👞', 'iOS7' => '', 'Hex' => '1F45E'),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/248.png>',
			'iOS5' => '👡',
			'iOS7' => '',
			'Hex'  => '1F461'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/249.png>',
			'iOS5' => '👠',
			'iOS7' => '',
			'Hex'  => '1F460'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/250.png>',
			'iOS5' => '👢',
			'iOS7' => '',
			'Hex'  => '1F462'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/251.png>',
			'iOS5' => '👕',
			'iOS7' => '',
			'Hex'  => '1F455'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/252.png>',
			'iOS5' => '👔',
			'iOS7' => '',
			'Hex'  => '1F454'
		),
		array('iOS2' => '', 'iOS5' => '👚', 'iOS7' => '', 'Hex' => '1F45A'),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/253.png>',
			'iOS5' => '👗',
			'iOS7' => '',
			'Hex'  => '1F457'
		),
		array('iOS2' => '', 'iOS5' => '🎽', 'iOS7' => '', 'Hex' => '1F3BD'),
		array('iOS2' => '', 'iOS5' => '👖', 'iOS7' => '', 'Hex' => '1F456'),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/254.png>',
			'iOS5' => '👘',
			'iOS7' => '',
			'Hex'  => '1F458'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/255.png>',
			'iOS5' => '👙',
			'iOS7' => '',
			'Hex'  => '1F459'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/261.png>',
			'iOS5' => '💼',
			'iOS7' => '',
			'Hex'  => '1F4BC'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/262.png>',
			'iOS5' => '👜',
			'iOS7' => '',
			'Hex'  => '1F45C'
		),
		array('iOS2' => '', 'iOS5' => '👝', 'iOS7' => '', 'Hex' => '1F45D'),
		array('iOS2' => '', 'iOS5' => '👛', 'iOS7' => '', 'Hex' => '1F45B'),
		array('iOS2' => '', 'iOS5' => '👓', 'iOS7' => '', 'Hex' => '1F453'),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/256.png>',
			'iOS5' => '🎀',
			'iOS7' => '',
			'Hex'  => '1F380'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/260.png>',
			'iOS5' => '🌂',
			'iOS7' => '',
			'Hex'  => '1F302'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/263.png>',
			'iOS5' => '💄',
			'iOS7' => '',
			'Hex'  => '1F484'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/035.png>',
			'iOS5' => '💛',
			'iOS7' => '',
			'Hex'  => '1F49B'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/036.png>',
			'iOS5' => '💙',
			'iOS7' => '',
			'Hex'  => '1F499'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/037.png>',
			'iOS5' => '💜',
			'iOS7' => '',
			'Hex'  => '1F49C'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/039.png>',
			'iOS5' => '💚',
			'iOS7' => '',
			'Hex'  => '1F49A'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/040.png>',
			'iOS5' => '❤',
			'iOS7' => '❤️',
			'Hex'  => '2764'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/041.png>',
			'iOS5' => '💔',
			'iOS7' => '',
			'Hex'  => '1F494'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/038.png>',
			'iOS5' => '💗',
			'iOS7' => '',
			'Hex'  => '1F497'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/042.png>',
			'iOS5' => '💓',
			'iOS7' => '',
			'Hex'  => '1F493'
		),
		array('iOS2' => '', 'iOS5' => '💕', 'iOS7' => '', 'Hex' => '1F495'),
		array('iOS2' => '', 'iOS5' => '💖', 'iOS7' => '', 'Hex' => '1F496'),
		array('iOS2' => '', 'iOS5' => '💞', 'iOS7' => '', 'Hex' => '1F49E'),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/043.png>',
			'iOS5' => '💘',
			'iOS7' => '',
			'Hex'  => '1F498'
		),
		array('iOS2' => '', 'iOS5' => '💌', 'iOS7' => '', 'Hex' => '1F48C'),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/105.png>',
			'iOS5' => '💋',
			'iOS7' => '',
			'Hex'  => '1F48B'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/264.png>',
			'iOS5' => '💍',
			'iOS7' => '',
			'Hex'  => '1F48D'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/265.png>',
			'iOS5' => '💎',
			'iOS7' => '',
			'Hex'  => '1F48E'
		),
		array('iOS2' => '', 'iOS5' => '👤', 'iOS7' => '', 'Hex' => '1F464'),
		array('iOS2' => '', 'iOS5' => '👥', 'iOS7' => '', 'Hex' => '1F465'),
		array('iOS2' => '', 'iOS5' => '💬', 'iOS7' => '', 'Hex' => '1F4AC'),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/104.png>',
			'iOS5' => '👣',
			'iOS7' => '',
			'Hex'  => '1F463'
		),
		array('iOS2' => '', 'iOS5' => '💭', 'iOS7' => '', 'Hex' => '1F4AD'),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/298.png>',
			'iOS5' => '🏠',
			'iOS7' => '',
			'Hex'  => '1F3E0'
		),
		array('iOS2' => '', 'iOS5' => '🏡', 'iOS7' => '', 'Hex' => '1F3E1'),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/299.png>',
			'iOS5' => '🏫',
			'iOS7' => '',
			'Hex'  => '1F3EB'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/300.png>',
			'iOS5' => '🏢',
			'iOS7' => '',
			'Hex'  => '1F3E2'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/301.png>',
			'iOS5' => '🏣',
			'iOS7' => '',
			'Hex'  => '1F3E3'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/302.png>',
			'iOS5' => '🏥',
			'iOS7' => '',
			'Hex'  => '1F3E5'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/303.png>',
			'iOS5' => '🏦',
			'iOS7' => '',
			'Hex'  => '1F3E6'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/304.png>',
			'iOS5' => '🏪',
			'iOS7' => '',
			'Hex'  => '1F3EA'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/305.png>',
			'iOS5' => '🏩',
			'iOS7' => '',
			'Hex'  => '1F3E9'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/306.png>',
			'iOS5' => '🏨',
			'iOS7' => '',
			'Hex'  => '1F3E8'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/307.png>',
			'iOS5' => '💒',
			'iOS7' => '',
			'Hex'  => '1F492'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/308.png>',
			'iOS5' => '⛪',
			'iOS7' => '⛪️',
			'Hex'  => '26EA'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/309.png>',
			'iOS5' => '🏬',
			'iOS7' => '',
			'Hex'  => '1F3EC'
		),
		array('iOS2' => '', 'iOS5' => '🏤', 'iOS7' => '', 'Hex' => '1F3E4'),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/310.png>',
			'iOS5' => '🌇',
			'iOS7' => '',
			'Hex'  => '1F307'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/311.png>',
			'iOS5' => '🌆',
			'iOS7' => '',
			'Hex'  => '1F306'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/313.png>',
			'iOS5' => '🏯',
			'iOS7' => '',
			'Hex'  => '1F3EF'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/314.png>',
			'iOS5' => '🏰',
			'iOS7' => '',
			'Hex'  => '1F3F0'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/315.png>',
			'iOS5' => '⛺',
			'iOS7' => '⛺️',
			'Hex'  => '26FA'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/316.png>',
			'iOS5' => '🏭',
			'iOS7' => '',
			'Hex'  => '1F3ED'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/317.png>',
			'iOS5' => '🗼',
			'iOS7' => '',
			'Hex'  => '1F5FC'
		),
		array('iOS2' => '', 'iOS5' => '🗾', 'iOS7' => '', 'Hex' => '1F5FE'),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/318.png>',
			'iOS5' => '🗻',
			'iOS7' => '',
			'Hex'  => '1F5FB'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/319.png>',
			'iOS5' => '🌄',
			'iOS7' => '',
			'Hex'  => '1F304'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/320.png>',
			'iOS5' => '🌅',
			'iOS7' => '',
			'Hex'  => '1F305'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/321.png>',
			'iOS5' => '🌃',
			'iOS7' => '',
			'Hex'  => '1F303'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/322.png>',
			'iOS5' => '🗽',
			'iOS7' => '',
			'Hex'  => '1F5FD'
		),
		array('iOS2' => '', 'iOS5' => '🌉', 'iOS7' => '', 'Hex' => '1F309'),
		array('iOS2' => '', 'iOS5' => '🎠', 'iOS7' => '', 'Hex' => '1F3A0'),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/324.png>',
			'iOS5' => '🎡',
			'iOS7' => '',
			'Hex'  => '1F3A1'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/325.png>',
			'iOS5' => '⛲',
			'iOS7' => '⛲️',
			'Hex'  => '26F2'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/326.png>',
			'iOS5' => '🎢',
			'iOS7' => '',
			'Hex'  => '1F3A2'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/327.png>',
			'iOS5' => '🚢',
			'iOS7' => '',
			'Hex'  => '1F6A2'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/329.png>',
			'iOS5' => '⛵',
			'iOS7' => '⛵️',
			'Hex'  => '26F5'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/328.png>',
			'iOS5' => '🚤',
			'iOS7' => '',
			'Hex'  => '1F6A4'
		),
		array('iOS2' => '', 'iOS5' => '🚣', 'iOS7' => '', 'Hex' => '1F6A3'),
		array('iOS2' => '', 'iOS5' => '⚓', 'iOS7' => '⚓️', 'Hex' => '2693'),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/331.png>',
			'iOS5' => '🚀',
			'iOS7' => '',
			'Hex'  => '1F680'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/330.png>',
			'iOS5' => '✈',
			'iOS7' => '✈️',
			'Hex'  => '2708'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/211.png>',
			'iOS5' => '💺',
			'iOS7' => '',
			'Hex'  => '1F4BA'
		),
		array('iOS2' => '', 'iOS5' => '🚁', 'iOS7' => '', 'Hex' => '1F681'),
		array('iOS2' => '', 'iOS5' => '🚂', 'iOS7' => '', 'Hex' => '1F682'),
		array('iOS2' => '', 'iOS5' => '🚊', 'iOS7' => '', 'Hex' => '1F68A'),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/342.png>',
			'iOS5' => '🚉',
			'iOS7' => '',
			'Hex'  => '1F689'
		),
		array('iOS2' => '', 'iOS5' => '🚞', 'iOS7' => '', 'Hex' => '1F69E'),
		array('iOS2' => '', 'iOS5' => '🚆', 'iOS7' => '', 'Hex' => '1F686'),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/343.png>',
			'iOS5' => '🚄',
			'iOS7' => '',
			'Hex'  => '1F684'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/344.png>',
			'iOS5' => '🚅',
			'iOS7' => '',
			'Hex'  => '1F685'
		),
		array('iOS2' => '', 'iOS5' => '🚈', 'iOS7' => '', 'Hex' => '1F688'),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/417.png>',
			'iOS5' => '🚇',
			'iOS7' => '',
			'Hex'  => '1F687'
		),
		array('iOS2' => '', 'iOS5' => '🚝', 'iOS7' => '', 'Hex' => '1F69D'),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/341.png>',
			'iOS5' => '🚋',
			'iOS7' => '',
			'Hex'  => '1F68B'
		),
		array('iOS2' => '', 'iOS5' => '🚃', 'iOS7' => '', 'Hex' => '1F683'),
		array('iOS2' => '', 'iOS5' => '🚎', 'iOS7' => '', 'Hex' => '1F68E'),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/336.png>',
			'iOS5' => '🚌',
			'iOS7' => '',
			'Hex'  => '1F68C'
		),
		array('iOS2' => '', 'iOS5' => '🚍', 'iOS7' => '', 'Hex' => '1F68D'),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/333.png>',
			'iOS5' => '🚙',
			'iOS7' => '',
			'Hex'  => '1F699'
		),
		array('iOS2' => '', 'iOS5' => '🚘', 'iOS7' => '', 'Hex' => '1F698'),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/334.png>',
			'iOS5' => '🚗',
			'iOS7' => '',
			'Hex'  => '1F697'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/335.png>',
			'iOS5' => '🚕',
			'iOS7' => '',
			'Hex'  => '1F695'
		),
		array('iOS2' => '', 'iOS5' => '🚖', 'iOS7' => '', 'Hex' => '1F696'),
		array('iOS2' => '', 'iOS5' => '🚛', 'iOS7' => '', 'Hex' => '1F69B'),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/340.png>',
			'iOS5' => '🚚',
			'iOS7' => '',
			'Hex'  => '1F69A'
		),
		array('iOS2' => '', 'iOS5' => '🚨', 'iOS7' => '', 'Hex' => '1F6A8'),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/337.png>',
			'iOS5' => '🚓',
			'iOS7' => '',
			'Hex'  => '1F693'
		),
		array('iOS2' => '', 'iOS5' => '🚔', 'iOS7' => '', 'Hex' => '1F694'),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/338.png>',
			'iOS5' => '🚒',
			'iOS7' => '',
			'Hex'  => '1F692'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/339.png>',
			'iOS5' => '🚑',
			'iOS7' => '',
			'Hex'  => '1F691'
		),
		array('iOS2' => '', 'iOS5' => '🚐', 'iOS7' => '', 'Hex' => '1F690'),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/332.png>',
			'iOS5' => '🚲',
			'iOS7' => '',
			'Hex'  => '1F6B2'
		),
		array('iOS2' => '', 'iOS5' => '🚡', 'iOS7' => '', 'Hex' => '1F6A1'),
		array('iOS2' => '', 'iOS5' => '🚟', 'iOS7' => '', 'Hex' => '1F69F'),
		array('iOS2' => '', 'iOS5' => '🚠', 'iOS7' => '', 'Hex' => '1F6A0'),
		array('iOS2' => '', 'iOS5' => '🚜', 'iOS7' => '', 'Hex' => '1F69C'),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/354.png>',
			'iOS5' => '💈',
			'iOS7' => '',
			'Hex'  => '1F488'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/353.png>',
			'iOS5' => '🚏',
			'iOS7' => '',
			'Hex'  => '1F68F'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/345.png>',
			'iOS5' => '🎫',
			'iOS7' => '',
			'Hex'  => '1F3AB'
		),
		array('iOS2' => '', 'iOS5' => '🚦', 'iOS7' => '', 'Hex' => '1F6A6'),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/347.png>',
			'iOS5' => '🚥',
			'iOS7' => '',
			'Hex'  => '1F6A5'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/348.png>',
			'iOS5' => '⚠',
			'iOS7' => '⚠️',
			'Hex'  => '26A0'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/349.png>',
			'iOS5' => '🚧',
			'iOS7' => '',
			'Hex'  => '1F6A7'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/350.png>',
			'iOS5' => '🔰',
			'iOS7' => '',
			'Hex'  => '1F530'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/346.png>',
			'iOS5' => '⛽',
			'iOS7' => '⛽️',
			'Hex'  => '26FD'
		),
		array('iOS2' => '', 'iOS5' => '🏮', 'iOS7' => '', 'Hex' => '1F3EE'),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/352.png>',
			'iOS5' => '🎰',
			'iOS7' => '',
			'Hex'  => '1F3B0'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/355.png>',
			'iOS5' => '♨',
			'iOS7' => '♨️',
			'Hex'  => '2668'
		),
		array('iOS2' => '', 'iOS5' => '🗿', 'iOS7' => '', 'Hex' => '1F5FF'),
		array('iOS2' => '', 'iOS5' => '🎪', 'iOS7' => '', 'Hex' => '1F3AA'),
		array('iOS2' => '', 'iOS5' => '🎭', 'iOS7' => '', 'Hex' => '1F3AD'),
		array('iOS2' => '', 'iOS5' => '📍', 'iOS7' => '', 'Hex' => '1F4CD'),
		array('iOS2' => '', 'iOS5' => '🚩', 'iOS7' => '', 'Hex' => '1F6A9'),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/358.png>',
			'iOS5' => '🇯🇵',
			'iOS7' => '',
			'Hex'  => '1F1EF_1F1F5'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/359.png>',
			'iOS5' => '🇰🇷',
			'iOS7' => '',
			'Hex'  => '1F1F0_1F1F7'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/367.png>',
			'iOS5' => '🇩🇪',
			'iOS7' => '',
			'Hex'  => '1F1E9_1F1EA'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/360.png>',
			'iOS5' => '🇨🇳',
			'iOS7' => '',
			'Hex'  => '1F1E8_1F1F3'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/361.png>',
			'iOS5' => '🇺🇸',
			'iOS7' => '',
			'Hex'  => '1F1FA_1F1F8'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/362.png>',
			'iOS5' => '🇫🇷',
			'iOS7' => '',
			'Hex'  => '1F1EB_1F1F7'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/363.png>',
			'iOS5' => '🇪🇸',
			'iOS7' => '',
			'Hex'  => '1F1EA_1F1F8'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/364.png>',
			'iOS5' => '🇮🇹',
			'iOS7' => '',
			'Hex'  => '1F1EE_1F1F9'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/365.png>',
			'iOS5' => '🇷🇺',
			'iOS7' => '',
			'Hex'  => '1F1F7_1F1FA'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/366.png>',
			'iOS5' => '🇬🇧',
			'iOS7' => '',
			'Hex'  => '1F1EC_1F1E7'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/312.png>',
			'iOS5' => '<img src=http://www.thyraz.info/emoji/312.png>',
			'iOS7' => '',
			'Hex'  => '<img src=http://www.thyraz.info/emoji/312.png>'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/119.png>',
			'iOS5' => '🐶',
			'iOS7' => '',
			'Hex'  => '1F436'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/123.png>',
			'iOS5' => '🐺',
			'iOS7' => '',
			'Hex'  => '1F43A'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/118.png>',
			'iOS5' => '🐱',
			'iOS7' => '',
			'Hex'  => '1F431'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/120.png>',
			'iOS5' => '🐭',
			'iOS7' => '',
			'Hex'  => '1F42D'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/121.png>',
			'iOS5' => '🐹',
			'iOS7' => '',
			'Hex'  => '1F439'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/122.png>',
			'iOS5' => '🐰',
			'iOS7' => '',
			'Hex'  => '1F430'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/124.png>',
			'iOS5' => '🐸',
			'iOS7' => '',
			'Hex'  => '1F438'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/125.png>',
			'iOS5' => '🐯',
			'iOS7' => '',
			'Hex'  => '1F42F'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/126.png>',
			'iOS5' => '🐨',
			'iOS7' => '',
			'Hex'  => '1F428'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/127.png>',
			'iOS5' => '🐻',
			'iOS7' => '',
			'Hex'  => '1F43B'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/128.png>',
			'iOS5' => '🐷',
			'iOS7' => '',
			'Hex'  => '1F437'
		),
		array('iOS2' => '', 'iOS5' => '🐽', 'iOS7' => '', 'Hex' => '1F43D'),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/129.png>',
			'iOS5' => '🐮',
			'iOS7' => '',
			'Hex'  => '1F42E'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/130.png>',
			'iOS5' => '🐗',
			'iOS7' => '',
			'Hex'  => '1F417'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/131.png>',
			'iOS5' => '🐵',
			'iOS7' => '',
			'Hex'  => '1F435'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/132.png>',
			'iOS5' => '🐒',
			'iOS7' => '',
			'Hex'  => '1F412'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/133.png>',
			'iOS5' => '🐴',
			'iOS7' => '',
			'Hex'  => '1F434'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/136.png>',
			'iOS5' => '🐑',
			'iOS7' => '',
			'Hex'  => '1F411'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/137.png>',
			'iOS5' => '🐘',
			'iOS7' => '',
			'Hex'  => '1F418'
		),
		array('iOS2' => '', 'iOS5' => '🐼', 'iOS7' => '', 'Hex' => '1F43C'),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/142.png>',
			'iOS5' => '🐧',
			'iOS7' => '',
			'Hex'  => '1F427'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/139.png>',
			'iOS5' => '🐦',
			'iOS7' => '',
			'Hex'  => '1F426'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/140.png>',
			'iOS5' => '🐤',
			'iOS7' => '',
			'Hex'  => '1F424'
		),
		array('iOS2' => '', 'iOS5' => '🐥', 'iOS7' => '', 'Hex' => '1F425'),
		array('iOS2' => '', 'iOS5' => '🐣', 'iOS7' => '', 'Hex' => '1F423'),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/141.png>',
			'iOS5' => '🐔',
			'iOS7' => '',
			'Hex'  => '1F414'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/138.png>',
			'iOS5' => '🐍',
			'iOS7' => '',
			'Hex'  => '1F40D'
		),
		array('iOS2' => '', 'iOS5' => '🐢', 'iOS7' => '', 'Hex' => '1F422'),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/143.png>',
			'iOS5' => '🐛',
			'iOS7' => '',
			'Hex'  => '1F41B'
		),
		array('iOS2' => '', 'iOS5' => '🐝', 'iOS7' => '', 'Hex' => '1F41D'),
		array('iOS2' => '', 'iOS5' => '🐜', 'iOS7' => '', 'Hex' => '1F41C'),
		array('iOS2' => '', 'iOS5' => '🐞', 'iOS7' => '', 'Hex' => '1F41E'),
		array('iOS2' => '', 'iOS5' => '🐌', 'iOS7' => '', 'Hex' => '1F40C'),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/144.png>',
			'iOS5' => '🐙',
			'iOS7' => '',
			'Hex'  => '1F419'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/162.png>',
			'iOS5' => '🐚',
			'iOS7' => '',
			'Hex'  => '1F41A'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/145.png>',
			'iOS5' => '🐠',
			'iOS7' => '',
			'Hex'  => '1F420'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/146.png>',
			'iOS5' => '🐟',
			'iOS7' => '',
			'Hex'  => '1F41F'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/148.png>',
			'iOS5' => '🐬',
			'iOS7' => '',
			'Hex'  => '1F42C'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/147.png>',
			'iOS5' => '🐳',
			'iOS7' => '',
			'Hex'  => '1F433'
		),
		array('iOS2' => '', 'iOS5' => '🐋', 'iOS7' => '', 'Hex' => '1F40B'),
		array('iOS2' => '', 'iOS5' => '🐄', 'iOS7' => '', 'Hex' => '1F404'),
		array('iOS2' => '', 'iOS5' => '🐏', 'iOS7' => '', 'Hex' => '1F40F'),
		array('iOS2' => '', 'iOS5' => '🐀', 'iOS7' => '', 'Hex' => '1F400'),
		array('iOS2' => '', 'iOS5' => '🐃', 'iOS7' => '', 'Hex' => '1F403'),
		array('iOS2' => '', 'iOS5' => '🐅', 'iOS7' => '', 'Hex' => '1F405'),
		array('iOS2' => '', 'iOS5' => '🐇', 'iOS7' => '', 'Hex' => '1F407'),
		array('iOS2' => '', 'iOS5' => '🐉', 'iOS7' => '', 'Hex' => '1F409'),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/134.png>',
			'iOS5' => '🐎',
			'iOS7' => '',
			'Hex'  => '1F40E'
		),
		array('iOS2' => '', 'iOS5' => '🐐', 'iOS7' => '', 'Hex' => '1F410'),
		array('iOS2' => '', 'iOS5' => '🐓', 'iOS7' => '', 'Hex' => '1F413'),
		array('iOS2' => '', 'iOS5' => '🐕', 'iOS7' => '', 'Hex' => '1F415'),
		array('iOS2' => '', 'iOS5' => '🐖', 'iOS7' => '', 'Hex' => '1F416'),
		array('iOS2' => '', 'iOS5' => '🐁', 'iOS7' => '', 'Hex' => '1F401'),
		array('iOS2' => '', 'iOS5' => '🐂', 'iOS7' => '', 'Hex' => '1F402'),
		array('iOS2' => '', 'iOS5' => '🐲', 'iOS7' => '', 'Hex' => '1F432'),
		array('iOS2' => '', 'iOS5' => '🐡', 'iOS7' => '', 'Hex' => '1F421'),
		array('iOS2' => '', 'iOS5' => '🐊', 'iOS7' => '', 'Hex' => '1F40A'),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/135.png>',
			'iOS5' => '🐫',
			'iOS7' => '',
			'Hex'  => '1F42B'
		),
		array('iOS2' => '', 'iOS5' => '🐪', 'iOS7' => '', 'Hex' => '1F42A'),
		array('iOS2' => '', 'iOS5' => '🐆', 'iOS7' => '', 'Hex' => '1F406'),
		array('iOS2' => '', 'iOS5' => '🐈', 'iOS7' => '', 'Hex' => '1F408'),
		array('iOS2' => '', 'iOS5' => '🐩', 'iOS7' => '', 'Hex' => '1F429'),
		array('iOS2' => '', 'iOS5' => '🐾', 'iOS7' => '', 'Hex' => '1F43E'),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/149.png>',
			'iOS5' => '💐',
			'iOS7' => '',
			'Hex'  => '1F490'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/150.png>',
			'iOS5' => '🌸',
			'iOS7' => '',
			'Hex'  => '1F338'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/151.png>',
			'iOS5' => '🌷',
			'iOS7' => '',
			'Hex'  => '1F337'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/152.png>',
			'iOS5' => '🍀',
			'iOS7' => '',
			'Hex'  => '1F340'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/153.png>',
			'iOS5' => '🌹',
			'iOS7' => '',
			'Hex'  => '1F339'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/154.png>',
			'iOS5' => '🌻',
			'iOS7' => '',
			'Hex'  => '1F33B'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/155.png>',
			'iOS5' => '🌺',
			'iOS7' => '',
			'Hex'  => '1F33A'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/156.png>',
			'iOS5' => '🍁',
			'iOS7' => '',
			'Hex'  => '1F341'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/157.png>',
			'iOS5' => '🍃',
			'iOS7' => '',
			'Hex'  => '1F343'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/158.png>',
			'iOS5' => '🍂',
			'iOS7' => '',
			'Hex'  => '1F342'
		),
		array('iOS2' => '', 'iOS5' => '🌿', 'iOS7' => '', 'Hex' => '1F33F'),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/161.png>',
			'iOS5' => '🌾',
			'iOS7' => '',
			'Hex'  => '1F33E'
		),
		array('iOS2' => '', 'iOS5' => '🍄', 'iOS7' => '', 'Hex' => '1F344'),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/160.png>',
			'iOS5' => '🌵',
			'iOS7' => '',
			'Hex'  => '1F335'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/159.png>',
			'iOS5' => '🌴',
			'iOS7' => '',
			'Hex'  => '1F334'
		),
		array('iOS2' => '', 'iOS5' => '🌲', 'iOS7' => '', 'Hex' => '1F332'),
		array('iOS2' => '', 'iOS5' => '🌳', 'iOS7' => '', 'Hex' => '1F333'),
		array('iOS2' => '', 'iOS5' => '🌰', 'iOS7' => '', 'Hex' => '1F330'),
		array('iOS2' => '', 'iOS5' => '🌱', 'iOS7' => '', 'Hex' => '1F331'),
		array('iOS2' => '', 'iOS5' => '🌼', 'iOS7' => '', 'Hex' => '1F33C'),
		array('iOS2' => '', 'iOS5' => '🌐', 'iOS7' => '', 'Hex' => '1F310'),
		array('iOS2' => '', 'iOS5' => '🌞', 'iOS7' => '', 'Hex' => '1F31E'),
		array('iOS2' => '', 'iOS5' => '🌝', 'iOS7' => '', 'Hex' => '1F31D'),
		array('iOS2' => '', 'iOS5' => '🌚', 'iOS7' => '', 'Hex' => '1F31A'),
		array('iOS2' => '', 'iOS5' => '🌑', 'iOS7' => '', 'Hex' => '1F311'),
		array('iOS2' => '', 'iOS5' => '🌒', 'iOS7' => '', 'Hex' => '1F312'),
		array('iOS2' => '', 'iOS5' => '🌓', 'iOS7' => '', 'Hex' => '1F313'),
		array('iOS2' => '', 'iOS5' => '🌔', 'iOS7' => '', 'Hex' => '1F314'),
		array('iOS2' => '', 'iOS5' => '🌕', 'iOS7' => '', 'Hex' => '1F315'),
		array('iOS2' => '', 'iOS5' => '🌖', 'iOS7' => '', 'Hex' => '1F316'),
		array('iOS2' => '', 'iOS5' => '🌗', 'iOS7' => '', 'Hex' => '1F317'),
		array('iOS2' => '', 'iOS5' => '🌘', 'iOS7' => '', 'Hex' => '1F318'),
		array('iOS2' => '', 'iOS5' => '🌜', 'iOS7' => '', 'Hex' => '1F31C'),
		array('iOS2' => '', 'iOS5' => '🌛', 'iOS7' => '', 'Hex' => '1F31B'),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/114.png>',
			'iOS5' => '🌙',
			'iOS7' => '',
			'Hex'  => '1F319'
		),
		array('iOS2' => '', 'iOS5' => '🌍', 'iOS7' => '', 'Hex' => '1F30D'),
		array('iOS2' => '', 'iOS5' => '🌎', 'iOS7' => '', 'Hex' => '1F30E'),
		array('iOS2' => '', 'iOS5' => '🌏', 'iOS7' => '', 'Hex' => '1F30F'),
		array('iOS2' => '', 'iOS5' => '🌋', 'iOS7' => '', 'Hex' => '1F30B'),
		array('iOS2' => '', 'iOS5' => '🌌', 'iOS7' => '', 'Hex' => '1F30C'),
		array('iOS2' => '', 'iOS5' => '🌠', 'iOS7' => '', 'Hex' => '1F320'),
		array('iOS2' => '', 'iOS5' => '⭐', 'iOS7' => '⭐️', 'Hex' => '2B50'),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/110.png>',
			'iOS5' => '☀',
			'iOS7' => '☀️',
			'Hex'  => '2600'
		),
		array('iOS2' => '', 'iOS5' => '⛅', 'iOS7' => '⛅️', 'Hex' => '26C5'),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/112.png>',
			'iOS5' => '☁',
			'iOS7' => '☁️',
			'Hex'  => '2601'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/115.png>',
			'iOS5' => '⚡',
			'iOS7' => '⚡️',
			'Hex'  => '26A1'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/111.png>',
			'iOS5' => '☔',
			'iOS7' => '☔️',
			'Hex'  => '2614'
		),
		array('iOS2' => '', 'iOS5' => '❄', 'iOS7' => '❄️', 'Hex' => '2744'),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/113.png>',
			'iOS5' => '⛄',
			'iOS7' => '⛄️',
			'Hex'  => '26C4'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/116.png>',
			'iOS5' => '🌀',
			'iOS7' => '🌀',
			'Hex'  => '1F300'
		),
		array('iOS2' => '', 'iOS5' => '🌁', 'iOS7' => '', 'Hex' => '1F301'),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/323.png>',
			'iOS5' => '🌈',
			'iOS7' => '',
			'Hex'  => '1F308'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/117.png>',
			'iOS5' => '🌊',
			'iOS7' => '',
			'Hex'  => '1F30A'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/163.png>',
			'iOS5' => '🎍',
			'iOS7' => '',
			'Hex'  => '1F38D'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/164.png>',
			'iOS5' => '💝',
			'iOS7' => '',
			'Hex'  => '1F49D'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/165.png>',
			'iOS5' => '🎎',
			'iOS7' => '',
			'Hex'  => '1F38E'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/166.png>',
			'iOS5' => '🎒',
			'iOS7' => '',
			'Hex'  => '1F392'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/167.png>',
			'iOS5' => '🎓',
			'iOS7' => '',
			'Hex'  => '1F393'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/168.png>',
			'iOS5' => '🎏',
			'iOS7' => '',
			'Hex'  => '1F38F'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/169.png>',
			'iOS5' => '🎆',
			'iOS7' => '',
			'Hex'  => '1F386'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/170.png>',
			'iOS5' => '🎇',
			'iOS7' => '',
			'Hex'  => '1F387'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/171.png>',
			'iOS5' => '🎐',
			'iOS7' => '',
			'Hex'  => '1F390'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/172.png>',
			'iOS5' => '🎑',
			'iOS7' => '',
			'Hex'  => '1F391'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/173.png>',
			'iOS5' => '🎃',
			'iOS7' => '',
			'Hex'  => '1F383'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/174.png>',
			'iOS5' => '👻',
			'iOS7' => '',
			'Hex'  => '1F47B'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/175.png>',
			'iOS5' => '🎅',
			'iOS7' => '',
			'Hex'  => '1F385'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/176.png>',
			'iOS5' => '🎄',
			'iOS7' => '',
			'Hex'  => '1F384'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/177.png>',
			'iOS5' => '🎁',
			'iOS7' => '',
			'Hex'  => '1F381'
		),
		array('iOS2' => '', 'iOS5' => '🎋', 'iOS7' => '', 'Hex' => '1F38B'),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/179.png>',
			'iOS5' => '🎉',
			'iOS7' => '',
			'Hex'  => '1F389'
		),
		array('iOS2' => '', 'iOS5' => '🎊', 'iOS7' => '', 'Hex' => '1F38A'),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/180.png>',
			'iOS5' => '🎈',
			'iOS7' => '',
			'Hex'  => '1F388'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/357.png>',
			'iOS5' => '🎌',
			'iOS7' => '',
			'Hex'  => '1F38C'
		),
		array('iOS2' => '', 'iOS5' => '🔮', 'iOS7' => '', 'Hex' => '1F52E'),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/184.png>',
			'iOS5' => '🎥',
			'iOS7' => '',
			'Hex'  => '1F3A5'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/183.png>',
			'iOS5' => '📷',
			'iOS7' => '',
			'Hex'  => '1F4F7'
		),
		array('iOS2' => '', 'iOS5' => '📹', 'iOS7' => '', 'Hex' => '1F4F9'),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/191.png>',
			'iOS5' => '📼',
			'iOS7' => '',
			'Hex'  => '1F4FC'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/181.png>',
			'iOS5' => '💿',
			'iOS7' => '',
			'Hex'  => '1F4BF'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/182.png>',
			'iOS5' => '📀',
			'iOS7' => '',
			'Hex'  => '1F4C0'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/190.png>',
			'iOS5' => '💽',
			'iOS7' => '',
			'Hex'  => '1F4BD'
		),
		array('iOS2' => '', 'iOS5' => '💾', 'iOS7' => '', 'Hex' => '1F4BE'),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/185.png>',
			'iOS5' => '💻',
			'iOS7' => '',
			'Hex'  => '1F4BB'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/187.png>',
			'iOS5' => '📱',
			'iOS7' => '',
			'Hex'  => '1F4F1'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/189.png>',
			'iOS5' => '☎',
			'iOS7' => '☎️',
			'Hex'  => '260E'
		),
		array('iOS2' => '', 'iOS5' => '📞', 'iOS7' => '', 'Hex' => '1F4DE'),
		array('iOS2' => '', 'iOS5' => '📟', 'iOS7' => '', 'Hex' => '1F4DF'),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/188.png>',
			'iOS5' => '📠',
			'iOS7' => '',
			'Hex'  => '1F4E0'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/196.png>',
			'iOS5' => '📡',
			'iOS7' => '',
			'Hex'  => '1F4E1'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/186.png>',
			'iOS5' => '📺',
			'iOS7' => '',
			'Hex'  => '1F4FA'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/195.png>',
			'iOS5' => '📻',
			'iOS7' => '',
			'Hex'  => '1F4FB'
		),
		array('iOS2' => '', 'iOS5' => '🔊', 'iOS7' => '', 'Hex' => '1F50A'),
		array('iOS2' => '', 'iOS5' => '🔉', 'iOS7' => '', 'Hex' => '1F509'),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/192.png>',
			'iOS5' => '🔈',
			'iOS7' => '',
			'Hex'  => '1F508'
		),
		array('iOS2' => '', 'iOS5' => '🔇', 'iOS7' => '', 'Hex' => '1F507'),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/178.png>',
			'iOS5' => '🔔',
			'iOS7' => '',
			'Hex'  => '1F514'
		),
		array('iOS2' => '', 'iOS5' => '🔕', 'iOS7' => '', 'Hex' => '1F515'),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/193.png>',
			'iOS5' => '📢',
			'iOS7' => '',
			'Hex'  => '1F4E2'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/194.png>',
			'iOS5' => '📣',
			'iOS7' => '',
			'Hex'  => '1F4E3'
		),
		array('iOS2' => '', 'iOS5' => '⏳', 'iOS7' => '', 'Hex' => '23F3'),
		array('iOS2' => '', 'iOS5' => '⌛', 'iOS7' => '⌛️', 'Hex' => '231B'),
		array('iOS2' => '', 'iOS5' => '⏰', 'iOS7' => '⏰', 'Hex' => '23F0'),
		array('iOS2' => '', 'iOS5' => '⌚', 'iOS7' => '⌚️', 'Hex' => '231A'),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/199.png>',
			'iOS5' => '🔓',
			'iOS7' => '',
			'Hex'  => '1F513'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/200.png>',
			'iOS5' => '🔒',
			'iOS7' => '',
			'Hex'  => '1F512'
		),
		array('iOS2' => '', 'iOS5' => '🔏', 'iOS7' => '', 'Hex' => '1F50F'),
		array('iOS2' => '', 'iOS5' => '🔐', 'iOS7' => '', 'Hex' => '1F510'),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/201.png>',
			'iOS5' => '🔑',
			'iOS7' => '',
			'Hex'  => '1F511'
		),
		array('iOS2' => '', 'iOS5' => '🔎', 'iOS7' => '', 'Hex' => '1F50E'),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/204.png>',
			'iOS5' => '💡',
			'iOS7' => '',
			'Hex'  => '1F4A1'
		),
		array('iOS2' => '', 'iOS5' => '🔦', 'iOS7' => '', 'Hex' => '1F526'),
		array('iOS2' => '', 'iOS5' => '🔆', 'iOS7' => '', 'Hex' => '1F506'),
		array('iOS2' => '', 'iOS5' => '🔅', 'iOS7' => '', 'Hex' => '1F505'),
		array('iOS2' => '', 'iOS5' => '🔌', 'iOS7' => '', 'Hex' => '1F50C'),
		array('iOS2' => '', 'iOS5' => '🔋', 'iOS7' => '', 'Hex' => '1F50B'),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/198.png>',
			'iOS5' => '🔍',
			'iOS7' => '',
			'Hex'  => '1F50D'
		),
		array('iOS2' => '', 'iOS5' => '🛁', 'iOS7' => '', 'Hex' => '1F6C1'),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/209.png>',
			'iOS5' => '🛀',
			'iOS7' => '',
			'Hex'  => '1F6C0'
		),
		array('iOS2' => '', 'iOS5' => '🚿', 'iOS7' => '', 'Hex' => '1F6BF'),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/210.png>',
			'iOS5' => '🚽',
			'iOS7' => '',
			'Hex'  => '1F6BD'
		),
		array('iOS2' => '', 'iOS5' => '🔧', 'iOS7' => '', 'Hex' => '1F527'),
		array('iOS2' => '', 'iOS5' => '🔩', 'iOS7' => '', 'Hex' => '1F529'),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/203.png>',
			'iOS5' => '🔨',
			'iOS7' => '',
			'Hex'  => '1F528'
		),
		array('iOS2' => '', 'iOS5' => '🚪', 'iOS7' => '', 'Hex' => '1F6AA'),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/214.png>',
			'iOS5' => '🚬',
			'iOS7' => '',
			'Hex'  => '1F6AC'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/215.png>',
			'iOS5' => '💣',
			'iOS7' => '',
			'Hex'  => '1F4A3'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/216.png>',
			'iOS5' => '🔫',
			'iOS7' => '',
			'Hex'  => '1F52B'
		),
		array('iOS2' => '', 'iOS5' => '🔪', 'iOS7' => '', 'Hex' => '1F52A'),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/217.png>',
			'iOS5' => '💊',
			'iOS7' => '',
			'Hex'  => '1F48A'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/218.png>',
			'iOS5' => '💉',
			'iOS7' => '',
			'Hex'  => '1F489'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/212.png>',
			'iOS5' => '💰',
			'iOS7' => '',
			'Hex'  => '1F4B0'
		),
		array('iOS2' => '', 'iOS5' => '💴', 'iOS7' => '', 'Hex' => '1F4B4'),
		array('iOS2' => '', 'iOS5' => '💵', 'iOS7' => '', 'Hex' => '1F4B5'),
		array('iOS2' => '', 'iOS5' => '💷', 'iOS7' => '', 'Hex' => '1F4B7'),
		array('iOS2' => '', 'iOS5' => '💶', 'iOS7' => '', 'Hex' => '1F4B6'),
		array('iOS2' => '', 'iOS5' => '💳', 'iOS7' => '', 'Hex' => '1F4B3'),
		array('iOS2' => '', 'iOS5' => '💸', 'iOS7' => '', 'Hex' => '1F4B8'),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/205.png>',
			'iOS5' => '📲',
			'iOS7' => '',
			'Hex'  => '1F4F2'
		),
		array('iOS2' => '', 'iOS5' => '📧', 'iOS7' => '', 'Hex' => '1F4E7'),
		array('iOS2' => '', 'iOS5' => '📥', 'iOS7' => '', 'Hex' => '1F4E5'),
		array('iOS2' => '', 'iOS5' => '📤', 'iOS7' => '', 'Hex' => '1F4E4'),
		array('iOS2' => '', 'iOS5' => '✉', 'iOS7' => '✉️', 'Hex' => '2709'),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/206.png>',
			'iOS5' => '📩',
			'iOS7' => '',
			'Hex'  => '1F4E9'
		),
		array('iOS2' => '', 'iOS5' => '📨', 'iOS7' => '', 'Hex' => '1F4E8'),
		array('iOS2' => '', 'iOS5' => '📯', 'iOS7' => '', 'Hex' => '1F4EF'),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/207.png>',
			'iOS5' => '📫',
			'iOS7' => '',
			'Hex'  => '1F4EB'
		),
		array('iOS2' => '', 'iOS5' => '📪', 'iOS7' => '', 'Hex' => '1F4EA'),
		array('iOS2' => '', 'iOS5' => '📬', 'iOS7' => '', 'Hex' => '1F4EC'),
		array('iOS2' => '', 'iOS5' => '📭', 'iOS7' => '', 'Hex' => '1F4ED'),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/208.png>',
			'iOS5' => '📮',
			'iOS7' => '',
			'Hex'  => '1F4EE'
		),
		array('iOS2' => '', 'iOS5' => '📦', 'iOS7' => '', 'Hex' => '1F4E6'),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/238.png>',
			'iOS5' => '📝',
			'iOS7' => '',
			'Hex'  => '1F4DD'
		),
		array('iOS2' => '', 'iOS5' => '📄', 'iOS7' => '', 'Hex' => '1F4C4'),
		array('iOS2' => '', 'iOS5' => '📃', 'iOS7' => '', 'Hex' => '1F4C3'),
		array('iOS2' => '', 'iOS5' => '📑', 'iOS7' => '', 'Hex' => '1F4D1'),
		array('iOS2' => '', 'iOS5' => '📊', 'iOS7' => '', 'Hex' => '1F4CA'),
		array('iOS2' => '', 'iOS5' => '📈', 'iOS7' => '', 'Hex' => '1F4C8'),
		array('iOS2' => '', 'iOS5' => '📉', 'iOS7' => '', 'Hex' => '1F4C9'),
		array('iOS2' => '', 'iOS5' => '📜', 'iOS7' => '', 'Hex' => '1F4DC'),
		array('iOS2' => '', 'iOS5' => '📋', 'iOS7' => '', 'Hex' => '1F4CB'),
		array('iOS2' => '', 'iOS5' => '📅', 'iOS7' => '', 'Hex' => '1F4C5'),
		array('iOS2' => '', 'iOS5' => '📆', 'iOS7' => '', 'Hex' => '1F4C6'),
		array('iOS2' => '', 'iOS5' => '📇', 'iOS7' => '', 'Hex' => '1F4C7'),
		array('iOS2' => '', 'iOS5' => '📁', 'iOS7' => '', 'Hex' => '1F4C1'),
		array('iOS2' => '', 'iOS5' => '📂', 'iOS7' => '', 'Hex' => '1F4C2'),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/202.png>',
			'iOS5' => '✂',
			'iOS7' => '✂️',
			'Hex'  => '2702'
		),
		array('iOS2' => '', 'iOS5' => '📌', 'iOS7' => '', 'Hex' => '1F4CC'),
		array('iOS2' => '', 'iOS5' => '📎', 'iOS7' => '', 'Hex' => '1F4CE'),
		array('iOS2' => '', 'iOS5' => '✒', 'iOS7' => '✒️', 'Hex' => '2712'),
		array('iOS2' => '', 'iOS5' => '✏', 'iOS7' => '✏️', 'Hex' => '270F'),
		array('iOS2' => '', 'iOS5' => '📏', 'iOS7' => '', 'Hex' => '1F4CF'),
		array('iOS2' => '', 'iOS5' => '📐', 'iOS7' => '', 'Hex' => '1F4D0'),
		array('iOS2' => '', 'iOS5' => '📕', 'iOS7' => '', 'Hex' => '1F4D5'),
		array('iOS2' => '', 'iOS5' => '📗', 'iOS7' => '', 'Hex' => '1F4D7'),
		array('iOS2' => '', 'iOS5' => '📘', 'iOS7' => '', 'Hex' => '1F4D8'),
		array('iOS2' => '', 'iOS5' => '📙', 'iOS7' => '', 'Hex' => '1F4D9'),
		array('iOS2' => '', 'iOS5' => '📓', 'iOS7' => '', 'Hex' => '1F4D3'),
		array('iOS2' => '', 'iOS5' => '📔', 'iOS7' => '', 'Hex' => '1F4D4'),
		array('iOS2' => '', 'iOS5' => '📒', 'iOS7' => '', 'Hex' => '1F4D2'),
		array('iOS2' => '', 'iOS5' => '📚', 'iOS7' => '', 'Hex' => '1F4DA'),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/239.png>',
			'iOS5' => '📖',
			'iOS7' => '',
			'Hex'  => '1F4D6'
		),
		array('iOS2' => '', 'iOS5' => '🔖', 'iOS7' => '', 'Hex' => '1F516'),
		array('iOS2' => '', 'iOS5' => '📛', 'iOS7' => '', 'Hex' => '1F4DB'),
		array('iOS2' => '', 'iOS5' => '🔬', 'iOS7' => '', 'Hex' => '1F52C'),
		array('iOS2' => '', 'iOS5' => '🔭', 'iOS7' => '', 'Hex' => '1F52D'),
		array('iOS2' => '', 'iOS5' => '📰', 'iOS7' => '', 'Hex' => '1F4F0'),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/240.png>',
			'iOS5' => '🎨',
			'iOS7' => '',
			'Hex'  => '1F3A8'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/237.png>',
			'iOS5' => '🎬',
			'iOS7' => '',
			'Hex'  => '1F3AC'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/241.png>',
			'iOS5' => '🎤',
			'iOS7' => '',
			'Hex'  => '1F3A4'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/242.png>',
			'iOS5' => '🎧',
			'iOS7' => '',
			'Hex'  => '1F3A7'
		),
		array('iOS2' => '', 'iOS5' => '🎼', 'iOS7' => '', 'Hex' => '1F3BC'),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/053.png>',
			'iOS5' => '🎵',
			'iOS7' => '',
			'Hex'  => '1F3B5'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/052.png>',
			'iOS5' => '🎶',
			'iOS7' => '',
			'Hex'  => '1F3B6'
		),
		array('iOS2' => '', 'iOS5' => '🎹', 'iOS7' => '', 'Hex' => '1F3B9'),
		array('iOS2' => '', 'iOS5' => '🎻', 'iOS7' => '', 'Hex' => '1F3BB'),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/243.png>',
			'iOS5' => '🎺',
			'iOS7' => '',
			'Hex'  => '1F3BA'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/244.png>',
			'iOS5' => '🎷',
			'iOS7' => '',
			'Hex'  => '1F3B7'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/245.png>',
			'iOS5' => '🎸',
			'iOS7' => '',
			'Hex'  => '1F3B8'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/234.png>',
			'iOS5' => '👾',
			'iOS7' => '',
			'Hex'  => '1F47E'
		),
		array('iOS2' => '', 'iOS5' => '🎮', 'iOS7' => '', 'Hex' => '1F3AE'),
		array('iOS2' => '', 'iOS5' => '🃏', 'iOS7' => '', 'Hex' => '1F0CF'),
		array('iOS2' => '', 'iOS5' => '🎴', 'iOS7' => '', 'Hex' => '1F3B4'),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/236.png>',
			'iOS5' => '🀄',
			'iOS7' => '🀄️',
			'Hex'  => '1F004'
		),
		array('iOS2' => '', 'iOS5' => '🎲', 'iOS7' => '', 'Hex' => '1F3B2'),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/235.png>',
			'iOS5' => '🎯',
			'iOS7' => '',
			'Hex'  => '1F3AF'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/219.png>',
			'iOS5' => '🏈',
			'iOS7' => '',
			'Hex'  => '1F3C8'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/220.png>',
			'iOS5' => '🏀',
			'iOS7' => '',
			'Hex'  => '1F3C0'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/221.png>',
			'iOS5' => '⚽',
			'iOS7' => '⚽️',
			'Hex'  => '26BD'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/222.png>',
			'iOS5' => '⚾',
			'iOS7' => '⚾️',
			'Hex'  => '26BE'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/223.png>',
			'iOS5' => '🎾',
			'iOS7' => '',
			'Hex'  => '1F3BE'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/225.png>',
			'iOS5' => '🎱',
			'iOS7' => '',
			'Hex'  => '1F3B1'
		),
		array('iOS2' => '', 'iOS5' => '🏉', 'iOS7' => '', 'Hex' => '1F3C9'),
		array('iOS2' => '', 'iOS5' => '🎳', 'iOS7' => '', 'Hex' => '1F3B3'),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/224.png>',
			'iOS5' => '⛳',
			'iOS7' => '⛳️',
			'Hex'  => '26F3'
		),
		array('iOS2' => '', 'iOS5' => '🚵', 'iOS7' => '', 'Hex' => '1F6B5'),
		array('iOS2' => '', 'iOS5' => '🚴', 'iOS7' => '', 'Hex' => '1F6B4'),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/356.png>',
			'iOS5' => '🏁',
			'iOS7' => '',
			'Hex'  => '1F3C1'
		),
		array('iOS2' => '', 'iOS5' => '🏇', 'iOS7' => '', 'Hex' => '1F3C7'),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/233.png>',
			'iOS5' => '🏆',
			'iOS7' => '',
			'Hex'  => '1F3C6'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/228.png>',
			'iOS5' => '🎿',
			'iOS7' => '',
			'Hex'  => '1F3BF'
		),
		array('iOS2' => '', 'iOS5' => '🏂', 'iOS7' => '', 'Hex' => '1F3C2'),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/226.png>',
			'iOS5' => '🏊',
			'iOS7' => '',
			'Hex'  => '1F3CA'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/227.png>',
			'iOS5' => '🏄',
			'iOS7' => '',
			'Hex'  => '1F3C4'
		),
		array('iOS2' => '', 'iOS5' => '🎣', 'iOS7' => '', 'Hex' => '1F3A3'),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/266.png>',
			'iOS5' => '☕',
			'iOS7' => '☕️',
			'Hex'  => '2615'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/267.png>',
			'iOS5' => '🍵',
			'iOS7' => '',
			'Hex'  => '1F375'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/271.png>',
			'iOS5' => '🍶',
			'iOS7' => '',
			'Hex'  => '1F376'
		),
		array('iOS2' => '', 'iOS5' => '🍼', 'iOS7' => '', 'Hex' => '1F37C'),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/268.png>',
			'iOS5' => '🍺',
			'iOS7' => '',
			'Hex'  => '1F37A'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/269.png>',
			'iOS5' => '🍻',
			'iOS7' => '',
			'Hex'  => '1F37B'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/270.png>',
			'iOS5' => '🍸',
			'iOS7' => '',
			'Hex'  => '1F378'
		),
		array('iOS2' => '', 'iOS5' => '🍹', 'iOS7' => '', 'Hex' => '1F379'),
		array('iOS2' => '', 'iOS5' => '🍷', 'iOS7' => '', 'Hex' => '1F377'),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/272.png>',
			'iOS5' => '🍴',
			'iOS7' => '',
			'Hex'  => '1F374'
		),
		array('iOS2' => '', 'iOS5' => '🍕', 'iOS7' => '', 'Hex' => '1F355'),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/273.png>',
			'iOS5' => '🍔',
			'iOS7' => '',
			'Hex'  => '1F354'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/274.png>',
			'iOS5' => '🍟',
			'iOS7' => '',
			'Hex'  => '1F35F'
		),
		array('iOS2' => '', 'iOS5' => '🍗', 'iOS7' => '', 'Hex' => '1F357'),
		array('iOS2' => '', 'iOS5' => '🍖', 'iOS7' => '', 'Hex' => '1F356'),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/275.png>',
			'iOS5' => '🍝',
			'iOS7' => '',
			'Hex'  => '1F35D'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/276.png>',
			'iOS5' => '🍛',
			'iOS7' => '',
			'Hex'  => '1F35B'
		),
		array('iOS2' => '', 'iOS5' => '🍤', 'iOS7' => '', 'Hex' => '1F364'),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/277.png>',
			'iOS5' => '🍱',
			'iOS7' => '',
			'Hex'  => '1F371'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/278.png>',
			'iOS5' => '🍣',
			'iOS7' => '',
			'Hex'  => '1F363'
		),
		array('iOS2' => '', 'iOS5' => '🍥', 'iOS7' => '', 'Hex' => '1F365'),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/279.png>',
			'iOS5' => '🍙',
			'iOS7' => '',
			'Hex'  => '1F359'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/280.png>',
			'iOS5' => '🍘',
			'iOS7' => '',
			'Hex'  => '1F358'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/281.png>',
			'iOS5' => '🍚',
			'iOS7' => '',
			'Hex'  => '1F35A'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/282.png>',
			'iOS5' => '🍜',
			'iOS7' => '',
			'Hex'  => '1F35C'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/283.png>',
			'iOS5' => '🍲',
			'iOS7' => '',
			'Hex'  => '1F372'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/286.png>',
			'iOS5' => '🍢',
			'iOS7' => '',
			'Hex'  => '1F362'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/287.png>',
			'iOS5' => '🍡',
			'iOS7' => '',
			'Hex'  => '1F361'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/285.png>',
			'iOS5' => '🍳',
			'iOS7' => '',
			'Hex'  => '1F373'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/284.png>',
			'iOS5' => '🍞',
			'iOS7' => '',
			'Hex'  => '1F35E'
		),
		array('iOS2' => '', 'iOS5' => '🍩', 'iOS7' => '', 'Hex' => '1F369'),
		array('iOS2' => '', 'iOS5' => '🍮', 'iOS7' => '', 'Hex' => '1F36E'),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/288.png>',
			'iOS5' => '🍦',
			'iOS7' => '',
			'Hex'  => '1F366'
		),
		array('iOS2' => '', 'iOS5' => '🍨', 'iOS7' => '', 'Hex' => '1F368'),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/289.png>',
			'iOS5' => '🍧',
			'iOS7' => '',
			'Hex'  => '1F367'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/290.png>',
			'iOS5' => '🎂',
			'iOS7' => '',
			'Hex'  => '1F382'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/291.png>',
			'iOS5' => '🍰',
			'iOS7' => '',
			'Hex'  => '1F370'
		),
		array('iOS2' => '', 'iOS5' => '🍪', 'iOS7' => '', 'Hex' => '1F36A'),
		array('iOS2' => '', 'iOS5' => '🍫', 'iOS7' => '', 'Hex' => '1F36B'),
		array('iOS2' => '', 'iOS5' => '🍬', 'iOS7' => '', 'Hex' => '1F36C'),
		array('iOS2' => '', 'iOS5' => '🍭', 'iOS7' => '', 'Hex' => '1F36D'),
		array('iOS2' => '', 'iOS5' => '🍯', 'iOS7' => '', 'Hex' => '1F36F'),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/292.png>',
			'iOS5' => '🍎',
			'iOS7' => '',
			'Hex'  => '1F34E'
		),
		array('iOS2' => '', 'iOS5' => '🍏', 'iOS7' => '', 'Hex' => '1F34F'),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/293.png>',
			'iOS5' => '🍊',
			'iOS7' => '',
			'Hex'  => '1F34A'
		),
		array('iOS2' => '', 'iOS5' => '🍋', 'iOS7' => '', 'Hex' => '1F34B'),
		array('iOS2' => '', 'iOS5' => '🍒', 'iOS7' => '', 'Hex' => '1F352'),
		array('iOS2' => '', 'iOS5' => '🍇', 'iOS7' => '', 'Hex' => '1F347'),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/294.png>',
			'iOS5' => '🍉',
			'iOS7' => '',
			'Hex'  => '1F349'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/295.png>',
			'iOS5' => '🍓',
			'iOS7' => '',
			'Hex'  => '1F353'
		),
		array('iOS2' => '', 'iOS5' => '🍑', 'iOS7' => '', 'Hex' => '1F351'),
		array('iOS2' => '', 'iOS5' => '🍈', 'iOS7' => '', 'Hex' => '1F348'),
		array('iOS2' => '', 'iOS5' => '🍌', 'iOS7' => '', 'Hex' => '1F34C'),
		array('iOS2' => '', 'iOS5' => '🍐', 'iOS7' => '', 'Hex' => '1F350'),
		array('iOS2' => '', 'iOS5' => '🍍', 'iOS7' => '', 'Hex' => '1F34D'),
		array('iOS2' => '', 'iOS5' => '🍠', 'iOS7' => '', 'Hex' => '1F360'),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/296.png>',
			'iOS5' => '🍆',
			'iOS7' => '',
			'Hex'  => '1F346'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/297.png>',
			'iOS5' => '🍅',
			'iOS7' => '',
			'Hex'  => '1F345'
		),
		array('iOS2' => '', 'iOS5' => '🌽', 'iOS7' => '', 'Hex' => '1F33D'),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/368.png>',
			'iOS5' => '1⃣',
			'iOS7' => '',
			'Hex'  => '0031_20E3'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/369.png>',
			'iOS5' => '2⃣',
			'iOS7' => '',
			'Hex'  => '0032_20E3'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/370.png>',
			'iOS5' => '3⃣',
			'iOS7' => '',
			'Heex' => '0034_20E3'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/369.png>',
			'iOS5' => '2⃣',
			'iOS7' => '',
			'Hex'  => '0032_20E3'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/377.png>',
			'iOS5' => '0⃣',
			'iOS7' => '',
			'Hex'  => '0030_20E3'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/372.png>',
			'iOS5' => '5⃣',
			'iOS7' => '',
			'Hex'  => '0035_20E3'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/373.png>',
			'iOS5' => '6⃣',
			'iOS7' => '',
			'Hex'  => '0036_20E3'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/374.png>',
			'iOS5' => '7⃣',
			'iOS7' => '',
			'Hex'  => '0037_20E3'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/375.png>',
			'iOS5' => '8⃣',
			'iOS7' => '',
			'Hex'  => '0038_20E3'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/376.png>',
			'iOS5' => '9⃣',
			'iOS7' => '',
			'Hex'  => '0039_20E3'
		),
		array('iOS2' => '', 'iOS5' => '🔟', 'iOS7' => '', 'Hex' => '1F51F'),
		array('iOS2' => '', 'iOS5' => '🔢', 'iOS7' => '', 'Hex' => '1F522'),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/378.png>',
			'iOS5' => '#⃣',
			'iOS7' => '',
			'Hex'  => '0023_20E3'
		),
		array('iOS2' => '', 'iOS5' => '🔣', 'iOS7' => '', 'Hex' => '1F523'),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/379.png>',
			'iOS5' => '⬆',
			'iOS7' => '⬆️',
			'Hex'  => '2B06'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/380.png>',
			'iOS5' => '⬇',
			'iOS7' => '⬇️',
			'Hex'  => '2B07'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/381.png>',
			'iOS5' => '⬅',
			'iOS7' => '⬅️',
			'Hex'  => '2B05'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/382.png>',
			'iOS5' => '➡',
			'iOS7' => '➡️',
			'Hex'  => '27A1'
		),
		array('iOS2' => '', 'iOS5' => '🔠', 'iOS7' => '', 'Hex' => '1F520'),
		array('iOS2' => '', 'iOS5' => '🔡', 'iOS7' => '', 'Hex' => '1F521'),
		array('iOS2' => '', 'iOS5' => '🔤', 'iOS7' => '', 'Hex' => '1F524'),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/383.png>',
			'iOS5' => '↗',
			'iOS7' => '↗️',
			'Hex'  => '2197'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/384.png>',
			'iOS5' => '↖',
			'iOS7' => '↖️',
			'Hex'  => '2196'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/385.png>',
			'iOS5' => '↘',
			'iOS7' => '↘️',
			'Hex'  => '2198'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/386.png>',
			'iOS5' => '↙',
			'iOS7' => '↙️',
			'Hex'  => '2199'
		),
		array('iOS2' => '', 'iOS5' => '↔', 'iOS7' => '↔️', 'Hex' => '2194'),
		array('iOS2' => '', 'iOS5' => '↕', 'iOS7' => '↕️', 'Hex' => '2195'),
		array('iOS2' => '', 'iOS5' => '🔄', 'iOS7' => '', 'Hex' => '1F504'),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/387.png>',
			'iOS5' => '◀',
			'iOS7' => '◀️',
			'Hex'  => '25C0'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/388.png>',
			'iOS5' => '▶',
			'iOS7' => '▶️',
			'Hex'  => '25B6'
		),
		array('iOS2' => '', 'iOS5' => '🔼', 'iOS7' => '', 'Hex' => '1F53C'),
		array('iOS2' => '', 'iOS5' => '🔽', 'iOS7' => '', 'Hex' => '1F53D'),
		array('iOS2' => '', 'iOS5' => '↩', 'iOS7' => '↩️', 'Hex' => '21A9'),
		array('iOS2' => '', 'iOS5' => '↪', 'iOS7' => '↪️', 'Hex' => '21AA'),
		array('iOS2' => '', 'iOS5' => 'ℹ', 'iOS7' => 'ℹ️', 'Hex' => '2139'),
		array('iOS2' => '<img src=http://www.thyraz.info/emoji/389.png>', 'iOS5' => '⏪', 'iOS7' => '', 'Hex' => '23EA'),
		array('iOS2' => '<img src=http://www.thyraz.info/emoji/390.png>', 'iOS5' => '⏩', 'iOS7' => '', 'Hex' => '23E9'),
		array('iOS2' => '', 'iOS5' => '⏫', 'iOS7' => '', 'Hex' => '23EB'),
		array('iOS2' => '', 'iOS5' => '⏬', 'iOS7' => '', 'Hex' => '23EC'),
		array('iOS2' => '', 'iOS5' => '⤵', 'iOS7' => '⤵️', 'Hex' => '2935'),
		array('iOS2' => '', 'iOS5' => '⤴', 'iOS7' => '⤴️', 'Hex' => '2934'),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/391.png>',
			'iOS5' => '🆗',
			'iOS7' => '',
			'Hex'  => '1F197'
		),
		array('iOS2' => '', 'iOS5' => '🔀', 'iOS7' => '', 'Hex' => '1F500'),
		array('iOS2' => '', 'iOS5' => '🔁', 'iOS7' => '', 'Hex' => '1F501'),
		array('iOS2' => '', 'iOS5' => '🔂', 'iOS7' => '', 'Hex' => '1F502'),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/392.png>',
			'iOS5' => '🆕',
			'iOS7' => '',
			'Hex'  => '1F195'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/394.png>',
			'iOS5' => '🆙',
			'iOS7' => '',
			'Hex'  => '1F199'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/395.png>',
			'iOS5' => '🆒',
			'iOS7' => '',
			'Hex'  => '1F192'
		),
		array('iOS2' => '', 'iOS5' => '🆓', 'iOS7' => '', 'Hex' => '1F193'),
		array('iOS2' => '', 'iOS5' => '🆖', 'iOS7' => '', 'Hex' => '1F196'),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/398.png>',
			'iOS5' => '📶',
			'iOS7' => '',
			'Hex'  => '1F4F6'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/396.png>',
			'iOS5' => '🎦',
			'iOS7' => '',
			'Hex'  => '1F3A6'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/397.png>',
			'iOS5' => '🈁',
			'iOS7' => '',
			'Hex'  => '1F201'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/403.png>',
			'iOS5' => '🈯',
			'iOS7' => '🈯️',
			'Hex'  => '1F22F'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/400.png>',
			'iOS5' => '🈳',
			'iOS7' => '',
			'Hex'  => '1F233'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/399.png>',
			'iOS5' => '🈵',
			'iOS7' => '',
			'Hex'  => '1F235'
		),
		array('iOS2' => '', 'iOS5' => '🈴', 'iOS7' => '', 'Hex' => '1F234'),
		array('iOS2' => '', 'iOS5' => '🈲', 'iOS7' => '', 'Hex' => '1F232'),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/401.png>',
			'iOS5' => '🉐',
			'iOS7' => '',
			'Hex'  => '1F250'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/402.png>',
			'iOS5' => '🈹',
			'iOS7' => '',
			'Hex'  => '1F239'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/404.png>',
			'iOS5' => '🈺',
			'iOS7' => '',
			'Hex'  => '1F23A'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/405.png>',
			'iOS5' => '🈶',
			'iOS7' => '',
			'Hex'  => '1F236'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/406.png>',
			'iOS5' => '🈚',
			'iOS7' => '🈚️',
			'Hex'  => '1F21A'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/410.png>',
			'iOS5' => '🚻',
			'iOS7' => '',
			'Hex'  => '1F6BB'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/411.png>',
			'iOS5' => '🚹',
			'iOS7' => '',
			'Hex'  => '1F6B9'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/412.png>',
			'iOS5' => '🚺',
			'iOS7' => '',
			'Hex'  => '1F6BA'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/413.png>',
			'iOS5' => '🚼',
			'iOS7' => '',
			'Hex'  => '1F6BC'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/418.png>',
			'iOS5' => '🚾',
			'iOS7' => '',
			'Hex'  => '1F6BE'
		),
		array('iOS2' => '', 'iOS5' => '🚰', 'iOS7' => '', 'Hex' => '1F6B0'),
		array('iOS2' => '', 'iOS5' => '🚮', 'iOS7' => '', 'Hex' => '1F6AE'),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/415.png>',
			'iOS5' => '🅿',
			'iOS7' => '🅿️',
			'Hex'  => '1F17F'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/416.png>',
			'iOS5' => '♿',
			'iOS7' => '♿️',
			'Hex'  => '267F'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/414.png>',
			'iOS5' => '🚭',
			'iOS7' => '',
			'Hex'  => '1F6AD'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/407.png>',
			'iOS5' => '🈷',
			'iOS7' => '',
			'Hex'  => '1F237'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/408.png>',
			'iOS5' => '🈸',
			'iOS7' => '',
			'Hex'  => '1F238'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/409.png>',
			'iOS5' => '🈂',
			'iOS7' => '',
			'Hex'  => '1F202'
		),
		array('iOS2' => '', 'iOS5' => 'Ⓜ', 'iOS7' => 'Ⓜ️', 'Hex' => '24C2'),
		array('iOS2' => '', 'iOS5' => '🛂', 'iOS7' => '', 'Hex' => '1F6C2'),
		array('iOS2' => '', 'iOS5' => '🛄', 'iOS7' => '', 'Hex' => '1F6C4'),
		array('iOS2' => '', 'iOS5' => '🛅', 'iOS7' => '', 'Hex' => '1F6C5'),
		array('iOS2' => '', 'iOS5' => '🛃', 'iOS7' => '', 'Hex' => '1F6C3'),
		array('iOS2' => '', 'iOS5' => '🉑', 'iOS7' => '', 'Hex' => '1F251'),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/419.png>',
			'iOS5' => '㊙',
			'iOS7' => '㊙️',
			'Hex'  => '3299'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/420.png>',
			'iOS5' => '㊗',
			'iOS7' => '㊗️',
			'Hex'  => '3297'
		),
		array('iOS2' => '', 'iOS5' => '🆑', 'iOS7' => '', 'Hex' => '1F191'),
		array('iOS2' => '', 'iOS5' => '🆘', 'iOS7' => '', 'Hex' => '1F198'),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/422.png>',
			'iOS5' => '🆔',
			'iOS7' => '',
			'Hex'  => '1F194'
		),
		array('iOS2' => '', 'iOS5' => '🚫', 'iOS7' => '', 'Hex' => '1F6AB'),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/421.png>',
			'iOS5' => '🔞',
			'iOS7' => '',
			'Hex'  => '1F51E'
		),
		array('iOS2' => '', 'iOS5' => '📵', 'iOS7' => '', 'Hex' => '1F4F5'),
		array('iOS2' => '', 'iOS5' => '🚯', 'iOS7' => '', 'Hex' => '1F6AF'),
		array('iOS2' => '', 'iOS5' => '🚱', 'iOS7' => '', 'Hex' => '1F6B1'),
		array('iOS2' => '', 'iOS5' => '🚳', 'iOS7' => '', 'Hex' => '1F6B3'),
		array('iOS2' => '', 'iOS5' => '🚷', 'iOS7' => '', 'Hex' => '1F6B7'),
		array('iOS2' => '', 'iOS5' => '🚸', 'iOS7' => '', 'Hex' => '1F6B8'),
		array('iOS2' => '', 'iOS5' => '⛔', 'iOS7' => '⛔️', 'Hex' => '26D4'),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/423.png>',
			'iOS5' => '✳',
			'iOS7' => '✳️',
			'Hex'  => '2733'
		),
		array('iOS2' => '', 'iOS5' => '❇', 'iOS7' => '❇️', 'Hex' => '2747'),
		array('iOS2' => '', 'iOS5' => '❎', 'iOS7' => '', 'Hex' => '274E'),
		array('iOS2' => '', 'iOS5' => '✅', 'iOS7' => '', 'Hex' => '2705'),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/424.png>',
			'iOS5' => '✴',
			'iOS7' => '✴️',
			'Hex'  => '2734'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/425.png>',
			'iOS5' => '💟',
			'iOS7' => '',
			'Hex'  => '1F49F'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/426.png>',
			'iOS5' => '🆚',
			'iOS7' => '',
			'Hex'  => '1F19A'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/427.png>',
			'iOS5' => '📳',
			'iOS7' => '',
			'Hex'  => '1F4F3'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/428.png>',
			'iOS5' => '📴',
			'iOS7' => '',
			'Hex'  => '1F4F4'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/445.png>',
			'iOS5' => '🅰',
			'iOS7' => '',
			'Hex'  => '1F170'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/446.png>',
			'iOS5' => '🅱',
			'iOS7' => '',
			'Hex'  => '1F171'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/447.png>',
			'iOS5' => '🆎',
			'iOS7' => '',
			'Hex'  => '1F18E'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/448.png>',
			'iOS5' => '🅾',
			'iOS7' => '',
			'Hex'  => '1F17E'
		),
		array('iOS2' => '', 'iOS5' => '💠', 'iOS7' => '', 'Hex' => '1F4A0'),
		array('iOS2' => '<img src=http://www.thyraz.info/emoji/197.png>', 'iOS5' => '➿', 'iOS7' => '', 'Hex' => '27BF'),
		array('iOS2' => '', 'iOS5' => '♻', 'iOS7' => '♻️', 'Hex' => '267B'),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/431.png>',
			'iOS5' => '♈',
			'iOS7' => '♈️',
			'Hex'  => '2648'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/432.png>',
			'iOS5' => '♉',
			'iOS7' => '♉️',
			'Hex'  => '2649'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/433.png>',
			'iOS5' => '♊',
			'iOS7' => '♊️',
			'Hex'  => '264A'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/434.png>',
			'iOS5' => '♋',
			'iOS7' => '♋️',
			'Hex'  => '264B'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/435.png>',
			'iOS5' => '♌',
			'iOS7' => '♌️',
			'Hex'  => '264C'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/436.png>',
			'iOS5' => '♍',
			'iOS7' => '♍️',
			'Hex'  => '264D'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/437.png>',
			'iOS5' => '♎',
			'iOS7' => '♎️',
			'Hex'  => '264E'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/438.png>',
			'iOS5' => '♏',
			'iOS7' => '♏️',
			'Hex'  => '264F'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/439.png>',
			'iOS5' => '♐',
			'iOS7' => '♐️',
			'Hex'  => '2650'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/440.png>',
			'iOS5' => '♑',
			'iOS7' => '♑️',
			'Hex'  => '2651'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/441.png>',
			'iOS5' => '♒',
			'iOS7' => '♒️',
			'Hex'  => '2652'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/442.png>',
			'iOS5' => '♓',
			'iOS7' => '♓️',
			'Hex'  => '2653'
		),
		array('iOS2' => '<img src=http://www.thyraz.info/emoji/443.png>', 'iOS5' => '⛎', 'iOS7' => '', 'Hex' => '26CE'),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/444.png>',
			'iOS5' => '🔯',
			'iOS7' => '',
			'Hex'  => '1F52F'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/351.png>',
			'iOS5' => '🏧',
			'iOS7' => '',
			'Hex'  => '1F3E7'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/429.png>',
			'iOS5' => '💹',
			'iOS7' => '',
			'Hex'  => '1F4B9'
		),
		array('iOS2' => '', 'iOS5' => '💲', 'iOS7' => '', 'Hex' => '1F4B2'),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/430.png>',
			'iOS5' => '💱',
			'iOS7' => '',
			'Hex'  => '1F4B1'
		),
		array('iOS2' => '©', 'iOS5' => '©', 'iOS7' => '', 'Hex' => '00A9'),
		array('iOS2' => '®', 'iOS5' => '®', 'iOS7' => '', 'Hex' => '00AE'),
		array('iOS2' => '™', 'iOS5' => '™', 'iOS7' => '', 'Hex' => '2122'),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/246.png>',
			'iOS5' => '〽',
			'iOS7' => '〽️',
			'Hex'  => '303D'
		),
		array('iOS2' => '', 'iOS5' => '〰', 'iOS7' => '', 'Hex' => '3030'),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/393.png>',
			'iOS5' => '🔝',
			'iOS7' => '',
			'Hex'  => '1F51D'
		),
		array('iOS2' => '', 'iOS5' => '🔚', 'iOS7' => '', 'Hex' => '1F51A'),
		array('iOS2' => '', 'iOS5' => '🔙', 'iOS7' => '', 'Hex' => '1F519'),
		array('iOS2' => '', 'iOS5' => '🔛', 'iOS7' => '', 'Hex' => '1F51B'),
		array('iOS2' => '', 'iOS5' => '🔜', 'iOS7' => '', 'Hex' => '1F51C'),
		array('iOS2' => '<img src=http://www.thyraz.info/emoji/465.png>', 'iOS5' => '❌', 'iOS7' => '', 'Hex' => '274C'),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/464.png>',
			'iOS5' => '⭕',
			'iOS7' => '⭕️',
			'Hex'  => '2B55'
		),
		array('iOS2' => '', 'iOS5' => '❗', 'iOS7' => '❗️', 'Hex' => '2757'),
		array('iOS2' => '', 'iOS5' => '❓', 'iOS7' => '', 'Hex' => '2753'),
		array('iOS2' => '<img src=http://www.thyraz.info/emoji/047.png>', 'iOS5' => '❕', 'iOS7' => '', 'Hex' => '2755'),
		array('iOS2' => '<img src=http://www.thyraz.info/emoji/048.png>', 'iOS5' => '❔', 'iOS7' => '', 'Hex' => '2754'),
		array('iOS2' => '', 'iOS5' => '🔃', 'iOS7' => '', 'Hex' => '1F503'),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/452.png>',
			'iOS5' => '🕛',
			'iOS7' => '',
			'Hex'  => '1F55B'
		),
		array('iOS2' => '', 'iOS5' => '🕧', 'iOS7' => '', 'Hex' => '1F567'),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/453.png>',
			'iOS5' => '🕐',
			'iOS7' => '',
			'Hex'  => '1F550'
		),
		array('iOS2' => '', 'iOS5' => '🕜', 'iOS7' => '', 'Hex' => '1F55C'),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/454.png>',
			'iOS5' => '🕑',
			'iOS7' => '',
			'Hex'  => '1F551'
		),
		array('iOS2' => '', 'iOS5' => '🕝', 'iOS7' => '', 'Hex' => '1F55D'),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/455.png>',
			'iOS5' => '🕒',
			'iOS7' => '',
			'Hex'  => '1F552'
		),
		array('iOS2' => '', 'iOS5' => '🕞', 'iOS7' => '', 'Hex' => '1F55E'),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/456.png>',
			'iOS5' => '🕓',
			'iOS7' => '',
			'Hex'  => '1F553'
		),
		array('iOS2' => '', 'iOS5' => '🕟', 'iOS7' => '', 'Hex' => '1F55F'),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/457.png>',
			'iOS5' => '🕔',
			'iOS7' => '',
			'Hex'  => '1F554'
		),
		array('iOS2' => '', 'iOS5' => '🕠', 'iOS7' => '', 'Hex' => '1F560'),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/458.png>',
			'iOS5' => '🕕',
			'iOS7' => '',
			'Hex'  => '1F555'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/459.png>',
			'iOS5' => '🕖',
			'iOS7' => '',
			'Hex'  => '1F556'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/460.png>',
			'iOS5' => '🕗',
			'iOS7' => '',
			'Hex'  => '1F557'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/461.png>',
			'iOS5' => '🕘',
			'iOS7' => '',
			'Hex'  => '1F558'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/462.png>',
			'iOS5' => '🕙',
			'iOS7' => '',
			'Hex'  => '1F559'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/463.png>',
			'iOS5' => '🕚',
			'iOS7' => '',
			'Hex'  => '1F55A'
		),
		array('iOS2' => '', 'iOS5' => '🕡', 'iOS7' => '', 'Hex' => '1F561'),
		array('iOS2' => '', 'iOS5' => '🕢', 'iOS7' => '', 'Hex' => '1F562'),
		array('iOS2' => '', 'iOS5' => '🕣', 'iOS7' => '', 'Hex' => '1F563'),
		array('iOS2' => '', 'iOS5' => '🕤', 'iOS7' => '', 'Hex' => '1F564'),
		array('iOS2' => '', 'iOS5' => '🕥', 'iOS7' => '', 'Hex' => '1F565'),
		array('iOS2' => '', 'iOS5' => '🕦', 'iOS7' => '', 'Hex' => '1F566'),
		array('iOS2' => '', 'iOS5' => '✖', 'iOS7' => '✖️', 'Hex' => '2716'),
		array('iOS2' => '', 'iOS5' => '➕', 'iOS7' => '', 'Hex' => '2795'),
		array('iOS2' => '', 'iOS5' => '➖', 'iOS7' => '', 'Hex' => '2796'),
		array('iOS2' => '', 'iOS5' => '➗', 'iOS7' => '', 'Hex' => '2797'),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/229.png>',
			'iOS5' => '♠',
			'iOS7' => '♠️',
			'Hex'  => '2660'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/230.png>',
			'iOS5' => '♥',
			'iOS7' => '♥️',
			'Hex'  => '2665'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/231.png>',
			'iOS5' => '♣',
			'iOS7' => '♣️',
			'Hex'  => '2663'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/232.png>',
			'iOS5' => '♦',
			'iOS7' => '♦️',
			'Hex'  => '2666'
		),
		array('iOS2' => '', 'iOS5' => '💮', 'iOS7' => '', 'Hex' => '1F4AE'),
		array('iOS2' => '', 'iOS5' => '💯', 'iOS7' => '', 'Hex' => '1F4AF'),
		array('iOS2' => '', 'iOS5' => '✔', 'iOS7' => '✔️', 'Hex' => '2714'),
		array('iOS2' => '', 'iOS5' => '☑', 'iOS7' => '☑️', 'Hex' => '2611'),
		array('iOS2' => '', 'iOS5' => '🔘', 'iOS7' => '', 'Hex' => '1F518'),
		array('iOS2' => '', 'iOS5' => '🔗', 'iOS7' => '', 'Hex' => '1F517'),
		array('iOS2' => '', 'iOS5' => '➰', 'iOS7' => '', 'Hex' => '27B0'),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/213.png>',
			'iOS5' => '🔱',
			'iOS7' => '',
			'Hex'  => '1F531'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/449.png>',
			'iOS5' => '🔲',
			'iOS7' => '',
			'Hex'  => '1F532'
		),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/451.png>',
			'iOS5' => '🔳',
			'iOS7' => '',
			'Hex'  => '1F533'
		),
		array('iOS2' => '', 'iOS5' => '◼', 'iOS7' => '◼️', 'Hex' => '25FC'),
		array('iOS2' => '', 'iOS5' => '◻', 'iOS7' => '◻️', 'Hex' => '25FB'),
		array('iOS2' => '', 'iOS5' => '◾', 'iOS7' => '◾️', 'Hex' => '25FE'),
		array('iOS2' => '', 'iOS5' => '◽', 'iOS7' => '◽️', 'Hex' => '25FD'),
		array('iOS2' => '', 'iOS5' => '▪', 'iOS7' => '▪️', 'Hex' => '25AA'),
		array('iOS2' => '', 'iOS5' => '▫', 'iOS7' => '▫️', 'Hex' => '25AB'),
		array('iOS2' => '', 'iOS5' => '🔺', 'iOS7' => '', 'Hex' => '1F53A'),
		array('iOS2' => '', 'iOS5' => '⬜', 'iOS7' => '⬜️', 'Hex' => '2B1C'),
		array('iOS2' => '', 'iOS5' => '⬛', 'iOS7' => '⬛️', 'Hex' => '2B1B'),
		array('iOS2' => '', 'iOS5' => '⚫', 'iOS7' => '⚫️', 'Hex' => '26AB'),
		array('iOS2' => '', 'iOS5' => '⚪', 'iOS7' => '⚪️', 'Hex' => '26AA'),
		array(
			'iOS2' => '<img src=http://www.thyraz.info/emoji/450.png>',
			'iOS5' => '🔴',
			'iOS7' => '',
			'Hex'  => '1F534'
		),
		array('iOS2' => '', 'iOS5' => '🔵', 'iOS7' => '', 'Hex' => '1F535'),
		array('iOS2' => '', 'iOS5' => '🔻', 'iOS7' => '', 'Hex' => '1F53B'),
		array('iOS2' => '', 'iOS5' => '🔶', 'iOS7' => '', 'Hex' => '1F536'),
		array('iOS2' => '', 'iOS5' => '🔷', 'iOS7' => '', 'Hex' => '1F537'),
		array('iOS2' => '', 'iOS5' => '🔸', 'iOS7' => '', 'Hex' => '1F538'),
		array('iOS2' => '', 'iOS5' => '🔹', 'iOS7' => '', 'Hex' => '1F539'),
		array('iOS2' => '', 'iOS5' => '⁉', 'iOS7' => '⁉️', 'Hex' => '2049'),
		array('iOS2' => '', 'iOS5' => '‼', 'iOS7' => '‼️', 'Hex' => '203C')
	);
}
