md c:\www\vandergraaf.local\wordpress\wp-content\plugins\vandergraaf-s3-storage
md c:\www\vandergraaf.local\wordpress\wp-content\plugins\vandergraaf-s3-storage\classes
md c:\www\vandergraaf.local\wordpress\wp-content\plugins\vandergraaf-s3-storage\aws-sdk

copy vandergraaf-s3-storage.php c:\www\vandergraaf.local\wordpress\wp-content\plugins\vandergraaf-s3-storage
copy vs3-updater.php c:\www\vandergraaf.local\wordpress\wp-content\plugins\vandergraaf-s3-storage

xcopy aws-sdk\*.* c:\www\vandergraaf.local\wordpress\wp-content\plugins\vandergraaf-s3-storage\aws-sdk /E
