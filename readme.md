#Exporter



##Requirements

-   PHP 5.2.0 * [Zip Extension](http://php.net/manual/en/class.ziparchive.php) *
    allow_url_fopen must be allowed in php.ini, as
    file_get_contents/file_put_contents is used to download assets



##Sample Template

~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
<h1>Entry ID: {exporter:entry_id}</h1>

{exp:channel:entries entry_id="{exporter:entry_id}" dynamic="no"}

    <h1>{title}</h1>

    {my_image_field}

    <img src="{exporter:capture_file}{image_url_or_path}{/exporter:capture_file}" alt="{image_title}" />

    {/my_image_field}

{/exp:channel:entries}
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~


