<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>The ZigZag Show</title>
    <meta name="keyword" content="Audio WaveForm, Video To Audio">
    <meta name="description" content="Convert video to gif or cut it out to shorter length">
    <link rel="stylesheet" type="text/css" href="styles.css">
    <script>
        // to prevent post from resubmitting form
        if ( window.history.replaceState ) {
            window.history.replaceState( null, null, window.location.href );
        }
    </script>
</head>
<body>
<div id="main-area">
    <div id="header">
        <h3 style="font-weight: bold; color: grey;">The ZigZag Show</h3>
        <h5 style="font-weight: bold; color: grey;">CDAC Intern TASK (php): Mon, 13-May-2019</h5>
        <ol>
            <li>Upload VIDEO FILE (file name should not containing any special characters and or white spaces!)</li>
            <li>Convert To Audio Format (mp3, aac).</li>
            <li>Show WaveForm</li>
            <li>Play audio</li>
            <li>Record audio</li>
        </ol>
        <ul>
            <li>Using Library - PHP ffmpeg</li>
        </ul>
    </div>
    <hr>
    <a href="audiorecording.php" style="display: compact">Record Audio</a> <br>
    <br>
    <?php
    if (!isset($_POST["submit"])) {
        ?>
        <form method="post" action="" enctype="multipart/form-data">
            <div id="form-contents">

                <label for="video_file">Your File</label> <br>
                <input id="video_file" type="file" name="user_video" value=""/> <br>

                <label for="extension">Convert to:</label> <br>
                <select name="extension" id="extension">
                    <option value="none">Default</option>
                    <option value="gif">GIF</option>
                    <!--                <option value="mp4">mp4</option>-->
                    <option value="mp3">MP3</option>
                    <option value="aac">AAC</option>
                    <option value="wav">WAV</option>
                    <!-- You can add other format here -->
                </select> <br>
                <label for="start_from">Start From:</label>
                <input type="text" name="start_from" id="start_from" value="" placeholder="example: 00:02:21"/>
                <br>
                <label for="length">Length:</label>
                <input type="text" name="length" id="length" value="" placeholder="example: 10"/> seconds
                <br>
                

                <input type="submit" name="submit" value="Edit">
            </div>
        </form>
        <?php //server side code
    }
    $input_dir = dirname(__FILE__) . "/input";
    $output_dir = dirname(__FILE__) . "/output";

    if (isset($_POST["submit"])) {
        if (file_exists($_FILES["user_video"]["tmp_name"])) {
            $temp_file = $_FILES["user_video"]["tmp_name"];

            $fileType = mime_content_type($temp_file);

            if(!preg_match('/audio\/x-wav/', $fileType))
            if (!preg_match('/video\/*/', $fileType) ) {
                //mime_type		video/mp4
                echo "<h4>Please upload a video</h4>";
                return;
            }

            // file name with extension
            $file = $_FILES["user_video"]["name"];

            // name without extension
            $filename = pathinfo($file, PATHINFO_FILENAME);

            // Default extension
            $default = pathinfo($file, PATHINFO_EXTENSION);

            // create special string from date to ensure filename is unique
            $date = date("Y-m-d H:i:s");
            $uploadtime = strtotime($date);

            // upload path
            $video_file = $input_dir . "/" . $uploadtime . "_" . $file;

            // check the specified extension
            if (!isset($_POST["extension"]) || $_POST["extension"] == "") {
                echo "<h4>Please set the output extension.</h4>";
                return;
            }
            $ext = $_POST["extension"]; // output extension
            if ($ext == "none") {
                $ext = $default;
            }

            // put file to input directory to make it easier to be processed with ffmpeg
//            move_uploaded_file ( string $filename , string $destination ) : bool
            $moved = move_uploaded_file($temp_file, $video_file);
            if ($moved) {
                // change php working directory to where ffmpeg binary file reside
//                chdir ( string $directory ) : bool
                chdir("binaries");

                $start_from = "00:00:00";
                // check the specified starting time
                if (isset($_POST["start_from"]) && $_POST["start_from"] != "") {
                    $start_from = $_POST["start_from"];
                }

                include_once("getid3/getid3/getid3.php");
//                $filename= $video_file;
                $getID3 = new getID3;
                $file = $getID3->analyze($video_file);
                echo "Duration: " . $file['playtime_string'];
                getid3_lib::CopyTagsToComments($file);


//				$length = 10;
                $length = $file['playtime_string'];
                // check the specified duration
                if (isset($_POST["length"]) && $_POST["length"] != "") {
                    $length = $_POST["length"];
                }
                set_time_limit(180);
                $output = "$output_dir/$uploadtime" . "_$filename.$ext";

				$process = exec("ffmpeg -t $length -ss $start_from -i $video_file -b:v 2048k $output 2>&1", $result);

                // if the input file is audio file
                $fileType2 = mime_content_type($output);
                if(preg_match('/audio\/*/', $fileType2)){
                    $png_file = "$output_dir/$uploadtime" . "_$filename.png";
                    $n = $uploadtime."_$filename.$ext";
                        $process2 = exec("ffmpeg -i $output -filter_complex showwavespic=s=640x120 -frames:v 1 $png_file 2>&1", $result1);

                    echo "<img src='http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]" . "output/$uploadtime" . "_$filename.png' alt='waveform'> <br>";
                    echo "<audio controls>";
                         echo " <source src='http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]" . "output/$uploadtime" . "_$filename.$ext' type='audio/mpeg'>";
                         echo "Your browser does not support the audio element.";
                    echo "</audio>";
                }
                // delete uploaded file from input folder to reserve disk space
                unlink($video_file);

                echo "<span>Converted: &nbsp; &nbsp; &nbsp;";
                    echo "<a href='http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]" . "output/$uploadtime" . "_$filename.$ext'>Download</a>";
                echo "</span>";

            }

        } // if of
        else {
            echo "<h3>Oops! No file was uploaded!</h3>";;
        }
    }
    unset($_POST["submit"]);
    $_POST["submit"]=NULL;
    ?>

</div>
</body>
</html>



