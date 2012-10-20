<?
//error_reporting(E_ALL & ~E_NOTICE);

if ( !defined('IS_PHP_CLI') ) define('IS_PHP_CLI', PHP_SAPI == 'cli');

function dumpString($data) {
	for ( $i = 0; $i < strlen($data); ++$i) {
		if ( $i % 32 == 0 ) {
			printf("\n%04X   ", $i);
		}
		$u = unpack('Cc', $data[$i]);
		printf('%02X ', $u['c']);
	}
	print "\n";	
}

function normalizePNG4iOS($oldData) {
	$pngheader = "\x89PNG\r\n\x1a\n";
	if ( substr($oldData, 0, strlen($pngheader)) != $pngheader ) {
		if ( IS_PHP_CLI ) echo "PNG  Invalid header\n";
		return false;
	}
	if ( IS_PHP_CLI ) echo "PNG  Converting PNG file\n";
	
	$data = $pngheader;
	$pos = strlen($pngheader);
	$tot = strlen($oldData);
	while ( $pos < $tot ) {
        # Reading chunk
        $chunkLength = substr($oldData, $pos, 4);
        $chunkLength = unpack('Nu', $chunkLength);
		$chunkLength = $chunkLength['u'];
        $chunkType = substr($oldData, $pos + 4, 4);
        $chunkData = substr($oldData, $pos + 8, $chunkLength);
        $chunkCRC = substr($oldData, $pos + $chunkLength + 8, 4);
        $chunkCRC = unpack('Nu', $chunkCRC);
		$chunkCRC = $chunkCRC['u'];
        $pos += $chunkLength + 12;
		if ( IS_PHP_CLI ) echo "PNG  Processing chunk $chunkType($chunkLength) #$pos/$tot\n";

        # Parsing the header chunk
        if ( $chunkType == 'IHDR' ) {
            $width = unpack('Nu', substr($chunkData, 0, 4));
			$width = $width['u'];
            $height = unpack('Nu', substr($chunkData, 4, 4));
			$height = $height['u'];
			if ( IS_PHP_CLI ) echo "PNG  Retrieved size of {$width}x{$height}\n";
		}

        # Parsing the image chunk
        if ( $chunkType == 'IDAT' ) {
            if ( ($chunkData = gzinflate($chunkData)) === false ) {
				if ( IS_PHP_CLI ) echo "PNG  Could not deflate IDAT\n";
            	return false;
			}


            # Swapping red & blue bytes for each pixel
            $tmp = '';
            for ( $y = 0; $y < $height; ++$y ) {
                $i = strlen($tmp);
                $tmp .= $chunkData[$i];
                for ( $x = 0; $x < $width; ++$x ) {
                    $i = strlen($tmp);
                    $tmp .= $chunkData[$i + 2];
                    $tmp .= $chunkData[$i + 1];
                    $tmp .= $chunkData[$i + 0];
                    $tmp .= $chunkData[$i + 3];
				}
			}

            # Compressing the image chunk
            $chunkData = $tmp;
            $chunkData = gzcompress($chunkData);

			//if ( IS_PHP_CLI ) dumpString($chunkData);

            $chunkLength = strlen($chunkData);
            $chunkCRC = crc32($chunkType . $chunkData);
			if ( IS_PHP_CLI ) echo "PNG  Chunk CRC {$chunkCRC}\n";
            // $chunkCRC = crc32($chunkData, $chunkCRC);
            // $chunkCRC = ($chunkCRC + 0x100000000) % 0x100000000;
        }

        # Removing CgBI chunk 
        if ( $chunkType != 'CgBI' ) {
            $data .= pack('N', $chunkLength);
            $data .= $chunkType;
            if ( $chunkLength > 0 ) {
                $data .= $chunkData;
			}
            $data .= pack('N', $chunkCRC);
		}

        # Stopping the PNG file parsing
        if ( $chunkType == 'IEND' ) {
            break;
        }
    }
	if ( IS_PHP_CLI ) echo "PNG  Conversion of PNG completed\n";
    return $data;
}
?>