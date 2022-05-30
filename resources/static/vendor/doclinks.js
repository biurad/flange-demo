'use strict';

// Wraps some elements in anchor tags referencing to the RadePHP documentation
$(function() {
    var $modal = $('#sourceCodeModal');
    var $templateCode = $modal.find('code.twig');

    function anchor(url, content) {
        return '<a class="doclink" target="_blank" href="' + url + '">' + content + '</a>';
    };

    // Wraps links to the RadePHP documentation
    $modal.find('.hljs-comment').each(function() {
        $(this).html($(this).html().replace(/https:\/\/biurad.com\/doc\/\/php\/rade\/[\w/.#-]+/g, function(url) {
            return anchor(url, url);
        }));
    });

    // Wraps Twig's tags
    $templateCode.find('.hljs-template-tag > .hljs-name').each(function() {
        var tag = $(this).text();

        if ('else' === tag || tag.match(/^end/)) {
            return;
        }

        var url = 'https://twig.symfony.com/doc/3.x/tags/' + tag + '.html#' + tag;

        $(this).html(anchor(url, tag));
    });

    // Wraps Twig's functions
    $templateCode.find('.hljs-template-variable > .hljs-name').each(function() {
        var func = $(this).text();

        var url = 'https://twig.symfony.com/doc/3.x/functions/' + func + '.html#' + func;

        $(this).html(anchor(url, func));
    });
});
