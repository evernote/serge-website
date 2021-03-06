<?php
    $section = 'callback-plugins';
    $subpage = 'ref-plugin-transform';
    $title = 'Guess Translations From Similar Already Translated Strings';
    include($_SERVER['DOCUMENT_ROOT'] . '/../inc/documentation-header.php');

    include($_SERVER['DOCUMENT_ROOT'] . '/../inc/version-selector.php');
?>

<h1><?php echo htmlspecialchars($title) ?></h1>

<p>Plugin source location: <code>&lt;serge_root&gt;/lib/Serge/Engine/Plugin/transform.pm</code></p>

<p>Plugin always attaches itself to the following callback phase: <code><a href="/docs/dev/callbacks/#get_translation">get_translation</a></code>.</p>

<p>Given a source string to translate, this plugin finds similar strings in the database by trying different transformation combinations, and then guesses the translation for the source string by applying the same chain of transformation to the pre-existing similar translation. Transformations include adjusting whitespace, ending punctuation, HTML tags, or applying different case.</p>

<p>This plugin speeds up the translation when small tweaks are applied to source strings in the course of product development.</p>

<h2>Example</h2>

<p>We get a new string, "HELLO, WORLD", that has no translation into Russian:</p>

<p class="notice">New string: "HELLO, WORLD" &rarr; no Russian translation</p>

<p>The plugin detects that in the Serge database associated with the current job there's a similar "Hello, world!" string, which already has a Russian translation:

<p class="notice">Existing string: "Hello, world!" &rarr; Russian translation: "Привет, мир!"</p>

<p>The plugin tries to guess the desired sequence of transformations needed to go from "Hello, world!" to "HELLO, WORLD":</p>

<p class="notice">
    "Hello, world!" &rarr; <strong>(???)</strong> &rarr; "HELLO, WORLD"<br/>
    "Hello, world!" &rarr; <strong>(uppercase) &rarr; (remove exclamation mark)</strong> &rarr; "HELLO, WORLD"
</p>

<p>Once the transformation has been determined, it applies it to the Russian translation of the original string:

<p class="notice">"Привет, мир!" &rarr; <strong>(uppercase) &rarr; (remove exclamation mark)</strong> &rarr; "ПРИВЕТ, МИР"</p>

<p>And the resulting translation, "ПРИВЕТ, МИР", is returned by the plugin as a fuzzy translation, ready for review:</p>

<p class="notice">New string: "HELLO, WORLD" &rarr; Russian translation: "ПРИВЕТ, МИР"</p>


<h2>Usage</h2>

<figure>
    <figcaption>example-project.serge</figcaption>
    <script language="text/x-config-neat">
jobs
{
    :sample-job
    {
        callback_plugins
        {
            :transform
            {
                plugin                   transform
            }
        }

        # other job parameters
        # ...
    }
}
</script>
</figure>

<?php include($_SERVER['DOCUMENT_ROOT'] . '/../inc/documentation-footer.php') ?>

