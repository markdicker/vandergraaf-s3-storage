<?php
/**
 * Plugin Name:     VdG S3 Storage
 * Description:     Allows Storage to S3
 * Author:          Mark Dicker
 * Author URI:      vandergraaf.io
 * Text Domain:     vandergraaf-s3-storage
 * Domain Path:     /languages
 * Version:         0.0.2
 *
 * @package         Vandergraaf_S3_Storage
 * 
 * License: GPLv3
 * License URI: http://www.gnu.org/licenses/gpl-3.0
 */

/**
 *	Copyright (C) 2012-2017 Mark Dicker (email: mark@markdicker.co.uk)
 *
 *	This program is free software; you can redistribute it and/or
 *	modify it under the terms of the GNU General Public License
 *	as published by the Free Software Foundation; either version 2
 *	of the License, or (at your option) any later version.
 *
 *	This program is distributed in the hope that it will be useful,
 *	but WITHOUT ANY WARRANTY; without even the implied warranty of
 *	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *	GNU General Public License for more details.
 *
 *	You should have received a copy of the GNU General Public License
 *	along with this program; if not, write to the Free Software
 *	Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

require ( "vs3-updater.php" );
require ( "aws-sdk/aws-autoloader.php" );

if ( ! class_exists( 'Van_der_Graaf_S3_Storage' ) ) :

class Van_der_Graaf_S3_Storage
{

    private $Updater = null;

    function __construct()
    {
    
        // Setup our page generator
        add_action( "vandergraaf_admin_init", array( $this, "setup_config" ), 10 );
        
        $Updater = new VanderGraaf_S3_Storage_Updater();

    }

    function setup_config( )
    {
     
        global $VdG;

        // Register the destinations depending on extension
        add_filter( "vandergraaf_config_tabs", array( $this, "config_tabs" ) );

        // Register a deployment option
        add_filter( "vandergraaf_deploy_to", array( $this, "deploy_to_s3" ) );

        // die( print_r( $VdG->Settings, true ) );
        $VdG->Settings->addSection( "s3_options", "Amazon S3 Options", array( $this, "description"), "vdg_s3_options" );
        

        $VdG->Settings->addField( "s3_options", "vdg_s3_options", array (
            'opt_name' => 'VDG_S3_KEY',
            'field_name' => 'VDG_S3_KEY',
            'label' => 'S3 Key',
            'opt_val' => '',
            'type' => 'text',
            'placeholder' => "S3KEY",
            'size' => 64
        ));

        $VdG->Settings->addField( "s3_options", "vdg_s3_options", array (
            'opt_name' => 'VDG_S3_SECRET',
            'field_name' => 'VDG_S3_SECRET',
            'label' => 'S3 Secret',
            'opt_val' => '',
            'type' => 'password',
            'placeholder' => "S3Secret",
            'size' => 64
        ));

        // $VdG->Settings->addField( "s3_options", "vdg_s3_options", array (
        //     'opt_name' => 'VDG_S3_PROFILE',
        //     'field_name' => 'VDG_S3_PROFILE',
        //     'label' => 'S3 Client Profile',
        //     'opt_val' => '',
        //     'type' => 'text',
        //     'placeholder' => "e.g default",
        //     'size' => 64
        // ));

        // $VdG->Settings->addField( "s3_options", "vdg_s3_options", array (
        //     'opt_name' => 'VDG_S3_VERSION',
        //     'field_name' => 'VDG_S3_VERSION',
        //     'label' => 'S3 Client Version',
        //     'opt_val' => '',
        //     'type' => 'password',
        //     'placeholder' => "S3KEY",
        //     'size' => 64
        // ));

        $VdG->Settings->addField( "s3_options", "vdg_s3_options", array (
            'opt_name' => 'VDG_S3_REGION',
            'field_name' => 'VDG_S3_REGION',
            'label' => 'Bucket Region',
            'opt_val' => '',
            'type' => 'text',
            'placeholder' => "e.g eu-west-1",
            'size' => 64
        ));

        $VdG->Settings->addField( "s3_options", "vdg_s3_options", array (
            'opt_name' => 'VDG_S3_BUCKET',
            'field_name' => 'VDG_S3_BUCKET',
            'label' => 'Bucket Name',
            'opt_val' => '',
            'type' => 'text',
            'placeholder' => "e.g bucket-name",
            'size' => 64
        ));

    }
    

    function deploy_to_s3( $deploy_to )
    {

        $deploy_to['s3'] = array( "Deploy to S3", "Van_der_Graaf_S3_Storage" );
        
        return $deploy_to;
    }


    function config_tabs( $tabs )
    {

        $tabs['s3s'] = array(

			"name" => "S3 Deploy",
			"fields" => "s3_options",
			"page" => "vdg_s3_options"

		);

        return $tabs;
    }

    function description( )
    {
        echo "Deploy static files to S3";
    }

    function createFolder( $root_path, $paths )
    {
        $final_path = ""; // we ignore $root_path for s3;

        foreach ( $paths as $path )
        {
            // protect against blank folder names
            if ( trim( $path ) == "" )
                continue;

            write_log( "Time Start (".time().")" );

            // // write_log( trim( $final_path . '/' . $path, "/" ) );
            $s3 = $this->s3();

            // write_log( "createFolder fp ". $final_path . '/' . $path );
            // write_log( "createFolder paths ");
            // write_log( $paths );

            write_log( "Time before doesObjectExist (".time().")" );

            if ( ! $s3->doesObjectExist( 
                    get_option( 'VDG_S3_BUCKET' ),
                    trim( $final_path . '/' . $path, "/" )
                ) 
            )         
            {
                $this->writeFile( trim( $final_path . '/' . $path, "/" ). '/' . 'index.html', PHP_EOL, 0644 );                
            }

            write_log( "Time end (".time().")" );

            $final_path .= '/' . $path;

        }

        return $final_path;
    }

    function writeFile( $path, $payload = "", $perms = 0644 )
    {

        $base_path = get_option( 'VDG_STATIC_ROOT' );

        write_log( "Time Start (".time().")" );

        // Get an instance of the S3 class
        $s3 = $this->s3();

        // remove our base path as we don't need it for s3
        $s3_path = str_replace ( $base_path."/", "", $path );

        $s3_path = trim( $s3_path, "/" );

        try {

            // if ( $s3->doesObjectExist( 
            //     get_option( 'VDG_S3_BUCKET' ),
            //     $s3_path
            // )) 
            // {
            //     $s3->deleteObject( array( 
            //         'Bucket' => get_option( 'VDG_S3_BUCKET' ),
            //         'Key' => $s3_path 
            //         )
            //     );
            // }
            
            // // write_log( $path." => ".$this->mime_type( $path ) );

            // write_log( "writeFile putObject" );

            write_log( "Time before putObject (".time().")" );

            $result = $s3->putObject( array( 
                'Bucket' => get_option( 'VDG_S3_BUCKET' ),
                'Key' => $s3_path,
                'ContentType' => $this->mime_type( $path ),
                'Body' => $payload,
                'ACL' => 'public-read'
            ));

        }
        catch( S3Exception $e )
        {
            // write_log( $e->getMessage );
        }

        write_log( "Time End (".time().")" );


    }

    function copyFile( $src, $dest )
    {

        // // write_log( "copy from [".$src."] to [".$dest."]" );

        $base_path = get_option( 'VDG_STATIC_ROOT' );

        write_log( "Time Start (".time().")" );

        // Get an instance of the S3 class
        $s3 = $this->s3();

        // remove our base path as we don't need it for s3
        $s3_path = str_replace ( $base_path."/", "", $dest );

        $s3_path = trim( $s3_path, "/" );

        $mime_type = $this->mime_type( $src );

        write_log( "Mime Type = ".$mime_type );

        switch( $mime_type )
        {
            case "image/jpeg" :
            case "image/gif" :
            case 'image/png' :    
            case 'image/svg+xml' :

                write_log( "Multipart upload " ); 

                $bucket = get_option( 'VDG_S3_BUCKET' );
                $keyname = $s3_path;
                $filename = $src;

                write_log( "Time before createMultipartUpload (".time().")" );

                $result = $s3->createMultipartUpload([
                    'Bucket'       => $bucket,
                    'Key'          => $keyname,
                    'ACL'          => 'public-read',
                    // 'StorageClass' => 'REDUCED_REDUNDANCY',
                    // 'Metadata'     => [
                    //     'param1' => 'value 1',
                    //     'param2' => 'value 2',
                    //     'param3' => 'value 3'
                    // ]
                ]);
                
                $uploadId = $result['UploadId'];
                
                write_log( $result ); 

                // Upload the file in parts.
                try {
                    
                    $file = fopen($filename, 'r');
                    
                    $partNumber = 1;
                    
                    while (!feof($file)) {

                        write_log( "Time before uploadPart (".time().")" );

                        $result = $s3->uploadPart([
                            'Bucket'     => $bucket,
                            'Key'        => $keyname,
                            'UploadId'   => $uploadId,
                            'PartNumber' => $partNumber,
                            'Body'       => fread($file, 5 * 1024 * 1024),
                        ]);

                        write_log( $result ); 

                        $parts['Parts'][$partNumber] = [
                            'PartNumber' => $partNumber,
                            'ETag' => $result['ETag'],
                        ];
                    
                        $partNumber++;
                
                        write_log( "Uploading part {$partNumber} of {$filename}." );
                    }
                    
                    fclose($file);

                } catch (S3Exception $e) {
                    
                    $result = $s3->abortMultipartUpload([
                        'Bucket'   => $bucket,
                        'Key'      => $keyname,
                        'UploadId' => $uploadId
                    ]);
                
                    write_log( "Upload of {$filename} failed." );
                }

                write_log( "Time before completeMultipartUpload (".time().")" );
                
                // Complete the multipart upload.
                $result = $s3->completeMultipartUpload([
                    'Bucket'   => $bucket,
                    'Key'      => $keyname,
                    'UploadId' => $uploadId,
                    'MultipartUpload'    => $parts,
                ]);
                
            default:
                
                write_log( "Time before putObject (".time().")" );

                try {
                    $result = $s3->putObject( array( 
                        'Bucket' => get_option( 'VDG_S3_BUCKET' ),
                        'Key' => $s3_path,
                        'ContentType' => $mime_type,
                        'SourceFile' => $src,
                        'ACL' => 'public-read'
                    ));
                }
                catch( S3Exception $e )
                {
                    // write_log( $e->getMessage );
                }

        }

        write_log( "Time end (".time().")" );


    }


    function s3()
    {
        $credentials = new Aws\Credentials\Credentials( get_option( 'VDG_S3_KEY' ), get_option( 'VDG_S3_SECRET' ) );

        // // write_log( $credentials );

        $s3 = new Aws\S3\S3Client([
            'version' => 'latest',
            'region'  => get_option( 'VDG_S3_REGION' ),
            'credentials' => array(
                'key' => get_option( 'VDG_S3_KEY' ), 
                'secret' => get_option( 'VDG_S3_SECRET' )
            )
            
            //$credentials
        ]);
        
        return $s3;
    }

    function mime_type( $path )
    {
        $parts=pathinfo( $path );
        
        // // write_log( $parts );

        $extension = $parts[ 'extension' ];


        switch( $extension )
        {
            case 'html' :
                return "text/html";
                break;
            
            case 'css' : 
                return "text/css";
                break;

            case 'js' :
                return "application/javascript";
                break;

            case 'jpg' :
            case 'jpeg' :
                return "image/jpeg";
                break;

            case 'gif' :
                return "image/gif";
                break;

            case 'png' :
                return "image/png";
                break;

            case 'svg' :
                return "image/svg+xml";
                break;
        }

        return "text/plain";
    }
}

$VdG_S3_Storage = new Van_der_Graaf_s3_storage();

endif;



if ( ! function_exists('write_log')) {
	function write_log ( $log )  {

	   if ( is_array( $log ) || is_object( $log ) ) {
		  error_log( print_r( $log, true ) );
	   } else {
		  error_log( $log );
       
        }
	}
 }
 