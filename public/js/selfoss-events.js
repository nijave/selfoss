selfoss.events = {

    /* last hash before hash change */
    lasthash: "",

    path:           null,
    lastpath:       null,
    reloadSamePath: false,

    section:        null,
    subsection:     false,
    lastSubsection: null,

    entryId:        null,

    /**
     * init events when page loads first time
     */
    init: function() {
        selfoss.events.navigation();

        // re-init on media query change
        if ((typeof window.matchMedia) != "undefined") {
            var mq = window.matchMedia("(min-width: 641px) and (max-width: 1024px)");
            if ((typeof mq.addListener) != "undefined")
                mq.addListener(selfoss.events.entries);
        }

        // window resize
        $("#nav-tags-wrapper").mCustomScrollbar({
            advanced:{
                updateOnContentResize: true
            }
        });
        $(window).bind("resize", selfoss.events.resize);
        selfoss.events.resize();

        if( location.hash == '' )
            selfoss.events.setHash($('#config').data('homepage'), 'all');
        
        // hash change event
        window.onhashchange = selfoss.events.hashChange;

        // process current hash
        selfoss.events.processHash();
    },
    
    
    /**
     * handle History change
     */
    hashChange: function() {
        if( selfoss.events.processHashChange )
            selfoss.events.processHash();
    },

    processHashChange: true,

    processHash: function(hash) {
        var hash = (typeof hash != 'undefined') ? hash : false;

        var done = function() {
            selfoss.events.processHashChange = true;
        };

        if( hash ) {
            selfoss.events.processHashChange = false;
            location.hash = hash
        }

        // assume the hash is encoded
        var hash = decodeURIComponent(location.href.split('#').splice(1).join('#'));

        if( !selfoss.events.reloadSamePath &&
            hash == selfoss.events.lasthash ) {
            done();
            return;
        }

        // parse hash
        var hashPath = hash.split('/');

        selfoss.events.section = hashPath[0];

        if( hashPath.length > 1 ) {
            selfoss.events.subsection = hashPath[1];
        } else
            selfoss.events.subsection = false;
        selfoss.events.lastpath = selfoss.events.path;
        selfoss.events.path = selfoss.events.section
                              + '/' + selfoss.events.subsection;

        var entryId = null;
        if( hashPath.length > 2 && (entryId = parseInt(hashPath[2])) )
            selfoss.events.entryId = entryId;
        else
            selfoss.events.entryId = null;

        selfoss.events.lasthash = hash;

        // do not reload list if list is the same and not explicitely requested
        if ( !selfoss.events.reloadSamePath &&
             selfoss.events.lastpath == selfoss.events.path ) {
            // scroll to entry if navigating using browser buttons
            if (selfoss.events.entryId && selfoss.events.processHashChange) {
                var entry = $('#entry' + selfoss.events.entryId);
                if( entry )
                    entry.get(0).scrollIntoView();
            }
            done();
            return;
        }

        // load items
        if( $.inArray(selfoss.events.section,
                      ["newest", "unread", "starred"]) > -1 ) {
            selfoss.filter.type = selfoss.events.section;
            selfoss.filter.tag = '';
            selfoss.filter.source = '';
            if( selfoss.events.subsection ) {
                selfoss.events.lastSubsection = selfoss.events.subsection;
                if( selfoss.events.subsection.substr(0, 4) == 'tag-') {
                    selfoss.filter.tag = selfoss.events.subsection.substr(4);
                } else if( selfoss.events.subsection.substr(0, 7) == 'source-') {
                    var sourceId = parseInt(selfoss.events.subsection.substr(7));
                    if( sourceId ) {
                        selfoss.filter.source = sourceId;
                        selfoss.filter.sourcesNav = true;
                    }
                } else if( selfoss.events.subsection != 'all' ) {
                    selfoss.ui.showError('Invalid subsection: '
                                         + selfoss.events.subsection);
                    done();
                    return;
                }
            }

            selfoss.events.reloadSamePath = false;
            selfoss.filterReset();

            $('#nav-filter > li').removeClass('active');
            $('#nav-filter-'+selfoss.events.section).addClass('active');
            selfoss.reloadList();
        } else if(hash=="sources") { // load sources
            if( selfoss.events.subsection ) {
                selfoss.ui.showError('Invalid subsection: '
                                     + selfoss.events.subsection);
                done();
                return;
            }

            if (selfoss.activeAjaxReq !== null)
                selfoss.activeAjaxReq.abort();

            selfoss.ui.refreshStreamButtons();
            $('#content').addClass('loading').html("");
            selfoss.activeAjaxReq = $.ajax({
                url: $('base').attr('href')+'sources',
                type: 'GET',
                success: function(data) {
                    $('#content').html(data);
                    selfoss.events.sources();
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    if (textStatus == "abort")
                        return;
                    else if (errorThrown)
                        selfoss.ui.showError('Load list error: '+
                                             textStatus+' '+errorThrown);
                },
                complete: function(jqXHR, textStatus) {
                    $('#content').removeClass('loading');
                }
            });
        } else {
            selfoss.ui.showError('Invalid section: ' + selfoss.events.section);
        }
        done();
    },
    

    setHash: function(section, subsection, entryId) {
        var section = (typeof section !== 'undefined') ? section : 'same';
        var subsection = (typeof subsection !== 'undefined') ? subsection : 'same';
        var entryId = (typeof entryId !== 'undefined') ? entryId : false;

        if( section == 'same' )
            section = selfoss.events.section;
        newHash = new Array(section);

        if(subsection == 'same')
            subsection = selfoss.events.lastSubsection;
        if(subsection)
            newHash.push(subsection.replace('%', '%25'));

        if(entryId)
            newHash.push(entryId);
        selfoss.events.processHash('#' + newHash.join('/'));
    },


    /**
     * set automatically the height of the tags and set scrollbar for div scrolling
     */
    resize: function() {
        // only set height if smartphone is false
        if(selfoss.isSmartphone()==false) {
            var start = $('#nav-tags-wrapper').position().top;
            var windowHeight = $(window).height();
            $('#nav-tags-wrapper').height(windowHeight - start - 100);
            $('#nav').show();
        } else {
            $('#nav-tags-wrapper').height("auto");
            $("#nav-tags-wrapper").mCustomScrollbar("disable",selfoss.isSmartphone());
        }
    }
};
