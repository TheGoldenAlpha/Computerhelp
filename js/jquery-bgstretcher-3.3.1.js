/*!
 bgStretcher 3.3.1 jQuery Plugin
 (c) 2010-2015 www.w3Blender.com
 For any questions and support please visit www.w3blender.com.
 */
;(function ($) {
    /*  Private variables  */
    var randomSequence, randomSequenceTmp, randomImageIndex, imageOptions, timeouts;

    randomSequence = [];
    randomSequenceTmp = [];
    randomImageIndex = 0;
    timeouts = [];
    imageOptions = {
        imageWidth: 0,
        imageHeight: 0,
        marginLeft: 0,
        marginTop: 0
    };

    // Save old instance of jQuery selector context.
    jQuery.fn.oldInitInstance = jQuery.fn.init;

    /**
     * Get last used selector.
     * @returns {string}
     */
    $.getLastSelector = function() {
        return $.getLastSelector.selector;
    };

    /**
     * Get last used selector object.
     * @returns {string}
     */
    $.getLastSelectorObject = function() {
        return $($.getLastSelector());
    };

    /**
     * Override jQuery selector context.
     * @param selector
     * @param context
     * @param rootjQuery
     * @returns {oldInitInstance}
     */
    $.fn.init = function(selector, context, rootjQuery)
    {
        if(typeof(selector) === 'string'){
            $.getLastSelector.selector = selector;
        }

        return new $.fn.oldInitInstance(selector, context, rootjQuery);
    };

    /**
     * Checks whether the browser supports the console object.
     * @returns {boolean}
     */
    var isConsoleExist = function()
    {
        return typeof(console) !== "undefined" && console !== null;
    };

    var debugOutput = function(message)
    {
        if(isConsoleExist()){
            console.log(message);
        } else {
            alert(message);
        }
    };

    /**
     * Checks whether the current browser is outdated or not.
     * @returns {boolean}
     */
    var isOldBrowser = function () {
        if (navigator.appName === "Microsoft Internet Explorer") {
            var re = new RegExp("MSIE ([0-9]{1,}[\.0-9]{0,})");
            if (re.exec(navigator.userAgent) !== null) {
                if (parseFloat(RegExp.$1) <= 6) {
                    return true;
                }
            }
        }

        return false;
    };

    var createUniqueId = function()
    {
        return Math.floor((1 + Math.random()) * 0x10000)
            .toString(16)
            .substring(1);
    };

    // Default settings
    var defaultSettings = {
        containerId: "",
        resizeProportionally: true,
        resizeAnimate: false,
        isPaused: false,
        images: [],
        links: [],
        content: [],
        imageWidth: 1024,
        imageHeight: 768,
        maxWidth: "auto",
        maxHeight: "auto",
        nextSlideDelay: 3000,
        slideShowSpeed: "normal",
        slideShow: true,
        transitionEffect: "fade", // none, fade, simpleSlide, superSlide
        slideDirection: "N", // N, S, W, E, (if superSlide - NW, NE, SW, SE)
        sequenceMode: "normal", // back, random
        buttonPrev: "",
        buttonNext: "",
        pagination: "",
        anchoring: "left top", // right bottom center
        anchoringImg: "left top", // right bottom center
        preloadImages: false,
        startElementIndex: 0,
        sliderCallbackFunc: null,
        rtl: false,
        displayFirstImageOnce: false
    };

    $.fn.bgStretcher = function(userOptions)
    {
        // Combine default and user settings.
        var settings = defaultSettings,
            $self = $(this);

        /**
         * Initializes the bgStretcher instance.
         * @param $thisEl The current element.
         * @returns {*}
         */
        var constructor = function()
        {
            // Setup settings
            settings = $.extend({}, defaultSettings, userOptions);

            if(settings.containerId == null || settings.containerId == undefined || settings.containerId == "") {
                settings.containerId = createUniqueId();
            }

            $self.settings = settings;
            timeouts[settings.containerId] = null;

            if($('#' + settings.containerId).length > 0) {
                debugOutput("Error: Duplicate element ID.");
                return false;
            }

            // Check if images exists
            if(settings.images.length <= 0){
                debugOutput("Error: Images array cannot be empty!");
                return false;
            }

            $self.addClass("bgstretcher-container");
            createInstance($self);

            $self.data("instance", $self);

            return $self;
        };

        /**
         * Create instance of the bgStretcher object. Setup handlers and add our code to DOM tree.
         * @param $instance
         */
        var createInstance = function($instance)
        {
            generateDomElements($instance);

            // Check if the container element exists
            if($self.length < 1){
                return;
            }

            var container = $('#' + $self.settings.containerId, $self);

            // Hide all elements
            container.find("LI").hide().css({ "z-index": 1, overflow: "hidden" });

            // Setup resize window handler
            $(window).unbind("resize.bgstretcher").bind("resize.bgstretcher", function(){
                $('.bgstretcher-container').each(function(i, el) {
                    var instance = $(el).data("instance");
                    performResize($(el), instance.settings);

                    if(instance.settings.transitionEffect == "superSlide") {
                        $(el).find("UL").css({top: 0});
                    }
                });
            });

            // Compatibility with old browsers which does not support fixed positioning.
            if (true === isOldBrowser()) {
                $(window).unbind("scroll").bind("scroll", function(){
                    //noinspection JSValidateTypes
                    $self.css("top", $(window).scrollTop());
                });
            }

            // Perform first resize to make sure the background lays out properly.
            performResize($self, $self.settings);

            var startElement = 0, originX = 0, originY = 0, children = container.find("LI");

            container.find("UL").css({ left: 0, top: 0 });
            container.find("LI.bgs-current").removeClass("bgs-current");
            container.find("LI").eq($self.settings.startElementIndex).addClass("bgs-current");

            /*  Rebuild images for simpleSlide  */
            if ($self.settings.transitionEffect === "simpleSlide") {
                if ($self.settings.slideDirection === "random") {
                    $self.settings.slideDirection = "normal";
                    debugOutput("The effect 'simpleSlide cannot be used in mode 'random'. The mode has been automatically switched to 'normal'.");
                }

                $(children).css({ float: "left", position: "static" }).show();

                if($self.settings.slideDirection === "NW" || $self.settings.slideDirection === "NE") {
                    $self.settings.slideDirection = "N";
                }

                if($self.settings.slideDirection === "SW" || $self.settings.slideDirection === "SE") {
                    $self.settings.slideDirection = "S";
                }

                if ($self.settings.slideDirection === "S" || $self.settings.slideDirection === "E") {
                    $self.settings.sequenceMode = "back";

                    $(children).removeClass("bgs-current");
                    $(children).eq($(children).length - $self.settings.startElementIndex - 1).addClass("bgs-current");

                    if ($self.settings.slideDirection === "E") {
                        originX = $(children).index(container.find("LI.bgs-current")) * container.width() * (-1);
                        originY = 0;
                    } else { // S
                        originY = $(children).index(container.find("LI.bgs-current")) * container.height() * (-1);
                        originX = 0;
                    }
                    container.find("UL").css({left: originX + "px", top: originY + "px"});
                } else {
                    settings.sequenceMode = "normal";
                    if ($self.settings.startElementIndex !== 0) {
                        if ($self.settings.slideDirection === "N") {
                            originY = $(children).index(container.find("LI.bgs-current")) * $(container).height() * (-1);
                            originX = 0;
                        } else { // W
                            originX = $(children).index(container.find("LI.bgs-current")) * $(container).width() * (-1);
                            originY = 0;
                        }
                        container.find("UL").css({left: originX + "px", top: originY + "px"});
                    }
                }
            }

            if ($($self.settings.buttonNext).length || $($self.settings.buttonPrev).length || $($self.settings.pagination).length) {
                if ($self.settings.sequenceMode === "random") {
                    debugOutput("'random' mode cannot be used when prevButton, nextButton and/or pagination is active.");
                } else {
                    /*  Prev and Next Buttons init  */
                    if ($($self.settings.buttonPrev).length) {
                        $($self.settings.buttonPrev).addClass("bgStretcherNav bgStretcherNavPrev");
                        $($self.settings.buttonPrev).click(function () {
                            $self.bgStretcher.buttonSlide($self, "prev");
                        });
                    }

                    if ($($self.settings.buttonNext).length) {
                        $($self.settings.buttonNext).addClass("bgStretcherNav bgStretcherNavNext");
                        $($self.settings.buttonNext).click(function () {
                            $self.bgStretcher.buttonSlide($self, "next");
                        });
                    }

                    /*  Pagination  */
                    if ($($self.settings.pagination).length) {
                        $self.bgStretcher.pagination($self);
                    }
                }
            }

            if($self.settings.rtl) {
                $(".bgstretcher-area", $self).css({textAlign: "right"});
            }

            /*  Random mode init  */
            if (settings.sequenceMode === "random") {
                var i = Math.floor(Math.random() * $(children).length);
                $self.bgStretcher.buildRandom($self, i);

                if (settings.transitionEffect !== "simpleSlide") {
                    $self.settings.startElementIndex = i;
                }

                startElement = i;
            } else {
                if ($self.settings.startElementIndex > ($(children).length - 1)) {
                    $self.settings.startElementIndex = 0;
                }

                startElement = $self.settings.startElementIndex;
                if (settings.transitionEffect === "simpleSlide") {
                    if ($self.settings.slideDirection === "S" || $self.settings.slideDirection === "E") {
                        startElement = $(children).length - 1 - $self.settings.startElementIndex;
                    }
                }
            }

            $(children).eq(startElement).show().addClass('bgs-current');
            $self.bgStretcher.preloadImage($(children).eq(startElement));

            /*  Start the slideshow  */
            if ($self.settings.slideShow && $(children).length > 1) {
                if(!$self.settings.isPaused) {
                    if($self.settings.displayFirstImageOnce == true){
                        container.find("LI.bgs-current").addClass('once');
                    }

                    timeouts[settings.containerId] = setTimeout(function() {
                        $self.bgStretcher.slideShow($self, $self.settings.sequenceMode, -1);
                    }, settings.nextSlideDelay);
                }
            }
        };

        /**
         * Perform proportional image resizing.
         * @param $container
         */
        var performResize = function($container, settings)
        {
            var windowWidth = 0,
                windowHeight = 0,
                containerWidth = 0,
                containerHeight = 0,
                imageWidth = 0,
                imageHeight = 0,
                bgContainer = $($container).find('.bgstretcher');

            /*var instance = $($container).data("instance");

             if(instance != undefined) {
             settings = instance.settings;
             }*/

            if($($container).get(0).nodeName == "BODY" || $($container).get(0).nodeName == "body") {
                $('.bgstretcher', $container).css({position: 'fixed', top: 0});
                windowWidth = parseInt($(window).width(), 10);
                windowHeight = parseInt($(window).height(), 10);
            } else {
                $('.bgstretcher', $container).css({position: 'absolute', top: 0});
                windowWidth = parseInt($container.width(), 10);
                windowHeight = parseInt($container.height(), 10);
                if (windowWidth==0 || windowHeight==0) {
                  windowWidth = $(".master").width();
                }
            }

            // Calculate container's width.
            if (settings.maxWidth !== "auto") {
                containerWidth = (windowWidth > settings.maxWidth) ? settings.maxWidth : windowWidth;
            } else {
                containerWidth = windowWidth;
                // GC NEW: 15th of March 2017 when Width is 0, use the width of first parents table
                widthPT = $("#diashow_1").width();
                if (!Restive.isMobile())
                  if (Restive.viewportW()<widthPT) widthPT = Restive.viewportW();
                if (windowWidth<20) windowWidth = widthPT;  
                containerSize = cssDimensions($container.get(0));
                if (containerSize.width.lastIndexOf("%")>=0) // when is in percent
                  widthImg = (parseInt(containerSize.width) * widthPT) / 100;
                if (containerWidth<20) containerWidth = widthImg;  
            }
            // Calculate container's height.
            if (settings.maxHeight !== "auto") {
                containerHeight = (windowHeight > settings.maxHeight) ? settings.maxHeight : windowHeight;
            } else {
                containerHeight = windowHeight + (navigator.userAgent.match( /(iPad|iPod|iPhone|Android)/ ) ? 250 : 0);
            }
            if (windowHeight==0 && containerHeight<=250) { // check if can be proportioned  (correction of containerHeight)
              containerHeight = (settings.imageHeight*containerWidth)/ settings.imageWidth;   
            }

            //  Update container's size
            bgContainer.width(containerWidth).height(containerHeight);

            if (false === settings.resizeProportionally){
                // NON-proportional resize
                imageWidth = containerWidth;
                imageHeight = containerHeight;
            } else {
                // Proportional resize
                var initialWidth = settings.imageWidth,
                    initialHeight = settings.imageHeight,
                    ratio = (initialHeight / initialWidth);

                imageWidth = containerWidth;
                imageHeight = Math.round(containerWidth * ratio);

                if (imageHeight < containerHeight) {
                    imageHeight = containerHeight;
                    imageWidth = Math.round(imageHeight / ratio);
                }
            }

            // Anchoring
            var marginLeft = 0,
                marginTop = 0,
                anchors;

            if (settings.anchoring !== "left top") {
                anchors = settings.anchoring.split(' ');
                if(anchors.length === 2){
                    if (anchors[0] === "right") {
                        marginLeft = windowWidth - containerWidth;
                    } else if(anchors[0] === "center") {
                        marginLeft = Math.round((windowWidth - containerWidth) / 2);
                    }

                    if (anchors[1] === "bottom") {
                        marginTop = (windowHeight - containerHeight);
                    } else if (anchors[1] === "center") {
                        marginTop = Math.round((windowHeight - containerHeight) / 2);
                    }

                    bgContainer.css({
                        marginLeft: (marginLeft + "px"),
                        marginTop: (marginTop + "px")
                    });
                }
            }

            marginLeft = 0;
            marginTop = 0;

            if (settings.anchoringImg !== "left top") {
                anchors = (settings.anchoringImg).split(' ');
                if(anchors.length === 2){
                    if (anchors[0] === "right") {
                        marginLeft = (containerWidth - imageWidth);
                    } else if(anchors[0] === 'center') {
                        marginLeft = Math.round((containerWidth - imageWidth) / 2);
                    }

                    if (anchors[1] === "bottom") {
                        marginTop = containerHeight - imageHeight;
                    } else if (anchors[1] === "center"){
                        marginTop = Math.round((containerHeight - imageHeight) / 2);
                    }
                }
            }

            imageOptions.marginLeft = marginLeft;
            imageOptions.marginTop = marginTop;

            //  Apply new size for images
            if ($container.find('LI:first').hasClass('swf-mode')) {
                if(typeof(SWFObject) === "undefined"){
                    debugOutput("SWFObject is required if you would like to use flash with bgStretcher.");
                } else {
                    var urlToSwf = $('#' + $container.settings.containerId).find('LI:first').html();
                    $('#' + $container.settings.containerId).find('LI:first').html('<div id="bgstretcher-flash">&nbsp;</div>');

                    var header = new SWFObject(urlToSwf, 'flash-obj', containerWidth, containerHeight, '9');
                    header.addParam('wmode', 'transparent');
                    header.write('bgstretcher-flash');
                }
            }

            imageOptions.imageWidth = imageWidth;
            imageOptions.imageHeight = imageHeight;

            var img = bgContainer.children("UL").children("LI.img-loaded").find("IMG");

            if (!settings.resizeAnimate) {
                $(img).css({
                    "marginLeft": imageOptions.marginLeft,
                    "marginTop": imageOptions.marginTop,
                    "width": imageOptions.imageWidth,
                    "height": imageOptions.imageHeight
                });
            } else {
                $(img).animate({
                    "marginLeft": imageOptions.marginLeft,
                    "marginTop": imageOptions.marginTop,
                    "width": imageOptions.imageWidth,
                    "height": imageOptions.imageHeight
                }, "normal");
            }

            bgContainer.find("LI").width(containerWidth).height(containerHeight);

            if ($self.settings.transitionEffect === "simpleSlide") {
                if ($self.settings.slideDirection === "W" || $self.settings.slideDirection === "E") {
                    bgContainer.children("UL").width(bgContainer.width() * bgContainer.find("LI").length).height(bgContainer.height());
                    if (bgContainer.find("LI").index(bgContainer.find("LI.bgs-current")) !== -1) {
                        bgContainer.children("UL").css({
                            left: ((bgContainer.find("LI").index(bgContainer.find("LI.bgs-current")) * bgContainer.width() * (-1)) + "px")
                        });
                    }
                } else {
                    bgContainer.children("UL").height(bgContainer.height() * bgContainer.find("LI").length).width(bgContainer.width());
                    if (bgContainer.find("LI").index(bgContainer.find("LI.bgs-current")) !== -1) {
                        bgContainer.children("UL").css({
                            top: ((bgContainer.find("LI").index(bgContainer.find("LI.bgs-current")) * $container.height() * (-1)) + "px")
                        });
                    }
                }
            }
        };

        /**
         * Generates HTML code for bgStretcher elements and adds it to the DOM tree.
         * @param instance
         */
        var generateDomElements = function(instance)
        {
            var code = "";

            instance.each(function(){
                $(this).wrapInner('<div class="bgstretcher-page" />').wrapInner('<div class="bgstretcher-area" />');
                code = '<div id="' + instance.settings.containerId + '" class="bgstretcher"><ul>';

                // Generate elements for each image
                if(instance.settings.images.length > 0) {
                    var itemExtension;

                    itemExtension = instance.settings.images[0].split('.');
                    itemExtension = itemExtension[itemExtension.length - 1];

                    if (itemExtension !== "swf") {
                        var itemIndex = 0;
                        for (var i = 0; i < instance.settings.images.length; i++) {
                            if ("simpleSlide" === instance.settings.transitionEffect && "back" === instance.settings.sequenceMode) {
                                itemIndex = instance.settings.images.length - 1 - i;
                            } else {
                                itemIndex = i;
                            }

                            if (true === instance.settings.preloadImages) {
                                code += '<li><span class="image-path">' + instance.settings.images[itemIndex] + '</span></li>';
                            } else {
// NEW GC: 15th of March 2017 to preload with correct size
                  mylink = (instance.settings.links[itemIndex]=='')? instance.settings.images[itemIndex] : instance.settings.links[itemIndex];
                  invisible = "document.getElementById('diashowComment').style.visibility='hidden';";
                  var sizes_arr;
                  sizes_array = instance.settings.sizes[itemIndex].split(',');
    iwidth = sizes_array[0];
    iheight = sizes_array[1];
    var strSize = "";
    var myRatio = iwidth/iheight;
    var imgRatio = instance.settings.imageWidth/instance.settings.imageHeight;
    if (myRatio<=imgRatio)
      strSize="height:"+instance.settings.imageHeight+"px";
    else
      strSize="width:"+instance.settings.imageWidth+"px";
                  target = (mylink=="#") ? "_self" : "_blank";
                  code += '<li class="img-loaded"><a href="' + mylink + '" target="'+ target +'"><img src="' + instance.settings.images[itemIndex] + '" alt="" onmouseover="ShowNote(event);" onmouseout="'+invisible+'" style="'+strSize+'"/></a></li>';
//                                code += '<li class="img-loaded"><img src="' + instance.settings.images[itemIndex] + '" alt="" /></li>';
                            }
                        }
                    } else {
                        code += '<li class="swf-mode">' + instance.settings.images[0] + '</li>';
                    }
                }

                code += '</ul></div>';

                var $currentInstance = $(this).children('.bgstretcher-area');

                $(code).prependTo($currentInstance);

                if(instance.settings.content.length > 0 && instance.settings.content.length == instance.settings.images.length) {
                    $(this).append('<div class="bgs-description-pane">' + instance.settings.content[instance.settings.startElementIndex] + '</div>');
                }

                $currentInstance.css({ position: 'relative' });
                $currentInstance.children('.bgstretcher-page').css({ position: "relative", 'z-index': 3 });
            });
        };

        // Entry point. Run the constructor.
        return constructor(this);
    };

    /**
     * Pre-load an image
     * @param $el
     * @returns {boolean}
     */
    $.fn.bgStretcher.preloadImage = function ($el)
    {
        if ($el.hasClass("img-loaded")) {
            // The image already loaded.
            return true;
        }

        $el.find("SPAN.image-path").each(function(){
            var imageUrl = $(this).html(),
                imageAlt = "",
                $parentEl = $(this).parent(),
                imageObject = new Image();

            // Load the image
            $(imageObject).load(function(){
                $(this).hide();
                $parentEl.prepend(this);
                $(this).fadeIn(100);
            }).error(function(){}).attr("src", imageUrl).attr("alt", imageAlt);

            $(imageObject).css({
                marginLeft: imageOptions.marginLeft + 'px',
                marginTop: imageOptions.marginTop + 'px',
                width: imageOptions.imageWidth + 'px',
                height: imageOptions.imageHeight + 'px'
            });
        });
        $el.addClass('img-loaded');

        return true;
    };

    var getbgStretcherInstance = function(self)
    {
        if(typeof(self) === 'undefined'){
            self = $.getLastSelectorObject();
            if(!self){
                return;
            }

            self = self.data('instance');
        }

        return self;
    };


    /**
     * Resume slideshow
     */
    $.fn.bgStretcher.play = function(self)
    {
        self = getbgStretcherInstance(self);
        self.settings.isPaused = false;
        $(self).bgStretcher.clearSliderTimeout(self);
        $(self).bgStretcher.slideShow(self, self.settings.sequenceMode, -1);
    };

    /**
     * Clear slider's timeout.
     */
    $.fn.bgStretcher.clearSliderTimeout = function(self)
    {
        self = getbgStretcherInstance(self);
        if (timeouts[self.settings.containerId] !== null) {
            clearTimeout(timeouts[self.settings.containerId]);
            timeouts[self.settings.containerId] = null;
        }
    };


    /**
     * Pause slider.
     */
    $.fn.bgStretcher.pause = function(self)
    {
        self = getbgStretcherInstance(self);
        self.settings.isPaused = true;
        $(self).bgStretcher.clearSliderTimeout(self);
    };

    /**
     * Destroy slider.
     */
    $.fn.bgStretcher.sliderDestroy = function(self)
    {
        self = getbgStretcherInstance(self);
        var content = $('.bgstretcher-page', self).html();

        $(self).html(content).removeClass('bgstretcher-container');
        $(self).bgStretcher.clearSliderTimeout(self);
        self.settings.isPaused = false;
    };

    /**
     * Initialize slideshow.
     * @param sequenceMode
     * @param nextImageIndex
     * @returns {boolean}
     */
    $.fn.bgStretcher.slideShow = function(self, sequenceMode, nextImageIndex)
    {
        // isSliderActive = true;

        var settings = $(self).data("instance").settings,
            selector = '#' + settings.containerId,
            children = $(selector).find("LI");

        if (children.length < 2){
            return true;
        }

        if(nextImageIndex == undefined || nextImageIndex == null) {
            nextImageIndex = -1;
        }

        var $currentEl = $(selector).find("LI.bgs-current"), $nextEl = false;
        $($currentEl).stop(true, true);

        if (nextImageIndex === -1) {
            switch (sequenceMode) {
                case "back":
                    $nextEl = $currentEl.prev();
                    if (!$nextEl.length) {
                        $nextEl = $(selector).find("LI:last");
                    }
                    break;

                case "random":
                    if (randomImageIndex === children.length) {
                        $(self).bgStretcher.buildRandom(self, randomSequence[children.length - 1]);
                        randomImageIndex = 0;
                    }

                    $nextEl = children.eq(randomSequence[randomImageIndex]);
                    randomImageIndex++;
                    break;

                default:
                    $nextEl = $currentEl.next();
                    if (!$nextEl.length) {
                        $nextEl = $(selector).find("LI:first");
                    }
            }
        } else {
            $nextEl = children.eq(nextImageIndex);
        }

        if(settings.content.length > 0) {
            $('> .bgs-description-pane', self).html(settings.content[$nextEl.index()]);
        }

        children.removeClass("bgs-current");
        $(self).bgStretcher.preloadImage($nextEl);
        $nextEl.addClass("bgs-current");

        switch (settings.transitionEffect) {
            case "fade":
                $(self).bgStretcher.effectFade(self, $currentEl, $nextEl);
                break;
            case "simpleSlide":
                $(self).bgStretcher.simpleSlide(self);
                break;
            case "superSlide":
                $(self).bgStretcher.superSlide(self, $currentEl, $nextEl, sequenceMode);
                break;
            default :
                $(self).bgStretcher.effectNone(self, $currentEl, $nextEl);
        }

        if ($(settings.pagination).find("LI").length) {
            $(settings.pagination).find("LI.showPage").removeClass("showPage");
            $(settings.pagination).find("LI").eq(children.index($(selector).find("LI.bgs-current"))).addClass("showPage");
        }

        // callback
        if (settings.sliderCallbackFunc) {
            if (typeof settings.sliderCallbackFunc === "function") {
                settings.sliderCallbackFunc.call();
            }
        }

        if (!settings.isPaused) {
            timeouts[settings.containerId] = setTimeout(function() {
                if($nextEl.parent().find('.once').length){
                    $nextEl.parent().find('.once').remove();
                }

                $(self).bgStretcher.slideShow(self, settings.sequenceMode, -1);
            }, self.settings.nextSlideDelay);
        }

        return true;
    };

    /**
     * Effect: none
     * @param $currentEl
     * @param $nextEl
     */
    $.fn.bgStretcher.effectNone = function(self, $currentEl, $nextEl)
    {
        $nextEl.show();
        $currentEl.hide();
    };

    /**
     * Effect: fade
     * @param $currentEl
     * @param $nextEl
     */
    $.fn.bgStretcher.effectFade = function(self, $currentEl, $nextEl)
    {
        var settings = $(self).data("instance").settings;

        $nextEl.fadeIn(settings.slideShowSpeed);
        $currentEl.fadeOut(settings.slideShowSpeed);
    };

    /**
     * Simple sliding.
     */
    $.fn.bgStretcher.simpleSlide = function(self)
    {
        var originX, originY,
            settings = $(self).data("instance").settings,
            selector = $('#' + settings.containerId);

        if (settings.slideDirection === "N" || settings.slideDirection === "S") {
            originY = selector.find("LI").index(selector.find("LI.bgs-current")) * selector.height() * (-1);
            originX = 0;
            selector.find("UL").css({ left: "0" });
        } else {
            originX = selector.find("LI").index(selector.find("LI.bgs-current")) * selector.width() * (-1);
            originY = 0;
            selector.find("UL").css({ top: "0" });
        }

        selector.find("UL").stop().animate({ left: originX + "px", top: originY + "px"}, settings.slideShowSpeed);
    };

    /**
     * Super sliding
     * @param $currentEl
     * @param $nextEl
     * @param sequenceMode
     */
    $.fn.bgStretcher.superSlide = function(self, $currentEl, $nextEl, sequenceMode) {
        var originX, originY,
            settings = $(self).data("instance").settings,
            selector = $('#' + settings.containerId);

        switch(settings.slideDirection)
        {
            case "S":
                originY = selector.height();
                originX = 0;
                break;

            case "E":
                originY = 0;
                originX = selector.width();
                break;

            case "W":
                originY = 0;
                originX = selector.width() * (-1);
                break;

            case "NW":
                originY = selector.height() * (-1);
                originX = selector.width() * (-1);
                break;

            case "NE":
                originY = selector.height() * (-1);
                originX = selector.width();
                break;

            case "SW":
                originY = selector.height();
                originX = selector.width() * (-1);
                break;

            case "SE":
                originY = selector.height();
                originX = selector.width();
                break;

            default:
                originY = selector.height() * (-1);
                originX = 0;
        }

        if (sequenceMode === "back") {
            $nextEl.css({"z-index": 2, top: originY + "px", left: originX + "px"});
            $nextEl.show();
            $nextEl.animate({left: "0px", top: "0px"}, settings.slideShowSpeed, function () {
                $currentEl.hide();
                $(this).css({"z-index": 1});
            });
        } else {
            $currentEl.css("z-index", 2);
            $nextEl.show();
            $currentEl.animate({left: originX + "px", top: originY + "px"}, settings.slideShowSpeed, function () {
                $(this).hide().css({"z-index": 1, top: "0px", left: "0px"});
            });
        }
    };

    /**
     * Build random sequence of images.
     * @param excludeIndex
     */
    $.fn.bgStretcher.buildRandom = function(self, excludeIndex) {
        var totalElementsSize = $(self).find("LI").length;
        var i, j, rt;
        for (i = 0; i < totalElementsSize; i++) {
            randomSequence[i] = i;
            randomSequenceTmp[i] = Math.random() * totalElementsSize;
        }
        for (i = 0; i < totalElementsSize; i++) {
            for (j = 0; j < (totalElementsSize - i - 1); j++) {
                if (randomSequenceTmp[j] > randomSequenceTmp[j + 1]) {
                    rt = randomSequenceTmp[j];
                    randomSequenceTmp[j] = randomSequenceTmp[j + 1];
                    randomSequenceTmp[j + 1] = rt;
                    rt = randomSequence[j];
                    randomSequence[j] = randomSequence[j + 1];
                    randomSequence[j + 1] = rt;
                }
            }
        }

        if (randomSequence[0] === excludeIndex) {
            rt = randomSequence[0];
            randomSequence[0] = randomSequence[totalElementsSize - 1];
            randomSequence[totalElementsSize - 1] = rt;
        }
    };

    /**
     * Initialize back and next buttons.
     * @param buttonType
     * @returns {boolean}
     */
    $.fn.bgStretcher.buttonSlide = function(self, buttonType)
    {
        var settings = self.settings,
            items = $('#' + settings.containerId).find("LI");

        if ($(items).length < 2){
            return false;
        }

        var mode = "";
        if (buttonType === "prev") {
            mode = 'back';
            if (settings.sequenceMode === "back"){
                mode = "normal";
            }
        } else {
            mode = settings.sequenceMode;
        }

        $(items).stop(true, true);
        $(self).bgStretcher.clearSliderTimeout(self);
        $(self).bgStretcher.slideShow(self, mode, -1);

        return false;
    };

    /**
     * Initalize pagination.
     * @returns {boolean}
     */
    $.fn.bgStretcher.pagination = function(self)
    {
        var items = $('#' + self.settings.containerId).find("LI"),
            totalElementsSize = items.length,
            code = "",
            i = 0,
            settings = self.settings;

        if (totalElementsSize > 0) {
            code += "<ul>";
            for (i = 0; i < totalElementsSize; i++) {
                code += "<li class=\"page-" + (i + 1) + "\"><a href=\"javascript:;\">" + (i + 1) + '</a></li>';
            }
            code += "</ul>";

            $(settings.pagination).html(code);
            $(settings.pagination).find("LI:first").addClass("showPage");

            $(settings.pagination).find("A").click(function () {
                if ($(this).parent().hasClass("showPage")) {
                    return false;
                }

                items.stop(true, true);
                $(self).bgStretcher.clearSliderTimeout(self);
                $(self).bgStretcher.slideShow(self, settings.sequenceMode, $(settings.pagination).find("A").index($(this)));

                return false;
            });

        }

        return false;
    };
})(jQuery);