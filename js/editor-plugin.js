(function($) {
    tinymce.create('tinymce.plugins.social_embed', {

        init : function(ed, url) {

            var self = this;

            // inject the widget scripts into the editor document
            // ed.onBeforeSetContent.add(function(ed, o) {

            // });

            ed.on('BeforeSetcontent', function(event){
                var head = ed.contentDocument.getElementsByTagName('head')[0],
                    s = [
                        '//platform.twitter.com/widgets.js',
                        '//connect.facebook.net/en_US/sdk.js#xfbml=1&version=v2.3',
                        '//platform.instagram.com/en_US/embeds.js'
                    ];
                for (var i = 0; i < s.length; i++) {
                    var el = ed.contentDocument.createElement('script');
                    head.appendChild(el);
                    el.src = s[i];
                }
            });

            // Register commands
            ed.addCommand('mceInsertSocialEmbed', function() {
                ed.windowManager.open({
                    title: 'Social Embed',
                    url: '/abc-social-admin',
                    height: 768,
                    width: 520,
                });
            });

            // add the button
            ed.addButton ('social_embed', {
                'title' : 'Social Embed',
                'image' : 'data:image/png;base64,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',
                'cmd': 'mceInsertSocialEmbed',
            });

            // replace the markup with the short code on save
            // this seems to happen a lot - as in more often than just on save
            ed.on('SaveContent', function(o){
            // ed.onSaveContent.add(function(ed, o) {

                var $content = $('<div>' + o.content + '</div>'), $twitterFrames;

                // transform the embeds back to short codes
                $content.find('.social-embed').each(function() {
                    var $el = $(this);
                    var shortCode = $el.attr('data-shortcode').replace(/'/g, '"');
                    $el.after(shortCode);
                    $el.remove();
                });

                // do some cleanup
                $content.find('#rufous-sandbox').closest('p').remove();
                $content.find('#rufous-sandbox').remove();
                $content.find('#fb-root').remove();
                $twitterFrames = $content.find("iframe[title='Twitter settings iframe']");
                $twitterFrames.closest('p').remove();
                $twitterFrames.remove();

                // get the content string
                var content = $content.html();

                // make sure we don't have a bung p tag
                if (content.replace(/^\s+|\s+$/g, '') == '<p>&nbsp;</p>') content = '';

                // set the content;
                o.content = content;
            });

            // // replace the short code with markup on load
            // // works alright for twitter, but fb not so much - insta - untested
            ed.on('SetContent', function(o) {

                // parse the content
                var re = /\[social_embed,url="([^"]+)"\]/gi,
                    m = ed.getContent().match(re);

                if (m) {

                    // handle m
                    var mCount = m.length,
                        rCount = 0,
                        rMap = {},
                        i;

                    // find all the matched
                    for (i=0; i < m.length; i++) {

                        // extract the match data
                        var mCur = m[i],
                            m2 = /url="([^"]+)"/.exec(mCur),
                            url = m2[1];

                        // get the fully parsed piece of html
                        $.get('/abc-social-admin/htmlfragment?pUrl=' + url, function(mCur, url, data, textStatus, jqXHR) {

                            // increment the request counter
                            rCount++;

                            // generate the token and the replacement html
                            var ii,
                                token = '[social_embed,url="' + url + '"]',
                                data =  '<div class="social-embed" data-shortcode="' + token.replace(/"/g, '\'') + '">' +
                                            data +
                                        '</div>';

                            // store the replacement data
                            rMap[mCur] = data;

                            // do the replacement once we get back all of the requests
                            if (rCount == mCount) {
                                var cont = ed.getContent();
                                for (ii=0; ii < m.length; ii++) {
                                    var key = m[ii];
                                    cont = cont.replace(key, rMap[key]);
                                }
                                ed.setContent(cont);
                            }

                        }.bind(null, mCur, url));
                    }
                }
            });
        },

        getInfo : function() {
            return {
                longname  : 'Social Embed',
                author    : 'Me',
                authorurl : 'http://github.com/azt3k',
                infourl   : 'http://github.com/azt3k/abc-silverstripe-social',
                version   : "0.1"
            };
        }
    });

    tinymce.PluginManager.add('social_embed', tinymce.plugins.social_embed);
})(jQuery);
