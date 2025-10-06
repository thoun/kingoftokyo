function slideToObjectAndAttach(game, object, destinationId, posX, posY) {
    var _this = this;
    return new Promise(function (resolve) {
        var destination = document.getElementById(destinationId);
        if (destination.contains(object)) {
            return resolve(false);
        }
        if (document.visibilityState === 'hidden' || game.instantaneousMode) {
            destination.appendChild(object);
            resolve(true);
        }
        else {
            object.style.zIndex = '10';
            var animation = (posX || posY) ?
                game.slideToObjectPos(object, destinationId, posX, posY) :
                game.slideToObject(object, destinationId);
            dojo.connect(animation, 'onEnd', dojo.hitch(_this, function () {
                object.style.top = 'unset';
                object.style.left = 'unset';
                object.style.position = 'relative';
                object.style.zIndex = 'unset';
                destination.appendChild(object);
                resolve(true);
            }));
            animation.play();
        }
    });
}
function transitionToObjectAndAttach(game, object, destinationId, zoom) {
    return new Promise(function (resolve) {
        var destination = document.getElementById(destinationId);
        if (destination.contains(object)) {
            return resolve(false);
        }
        if (document.visibilityState === 'hidden' || game.instantaneousMode) {
            destination.appendChild(object);
            resolve(true);
        }
        else {
            var destinationBR = document.getElementById(destinationId).getBoundingClientRect();
            var originBR = object.getBoundingClientRect();
            var deltaX = destinationBR.left - originBR.left;
            var deltaY = destinationBR.top - originBR.top;
            object.style.zIndex = '10';
            object.style.transition = "transform 0.5s linear";
            object.style.transform = "translate(".concat(deltaX / zoom, "px, ").concat(deltaY / zoom, "px)");
            setTimeout(function () {
                object.style.zIndex = null;
                object.style.transition = null;
                object.style.transform = null;
                destination.appendChild(object);
                resolve(true);
            }, 500);
        }
    });
}
function formatTextIcons(rawText) {
    if (!rawText) {
        return '';
    }
    return rawText
        .replace(/\[Star\]/ig, '<span class="icon points"></span>')
        .replace(/\[Heart\]/ig, '<span class="icon health"></span>')
        .replace(/\[Energy\]/ig, '<span class="icon energy"></span>')
        .replace(/\[Skull\]/ig, '<span class="icon dead"></span>')
        .replace(/\[dic?e1\]/ig, '<span class="dice-icon dice1"></span>')
        .replace(/\[dic?e2\]/ig, '<span class="dice-icon dice2"></span>')
        .replace(/\[dic?e3\]/ig, '<span class="dice-icon dice3"></span>')
        .replace(/\[dic?eHeart\]/ig, '<span class="dice-icon dice4"></span>')
        .replace(/\[dic?eEnergy\]/ig, '<span class="dice-icon dice5"></span>')
        .replace(/\[dic?eSmash\]/ig, '<span class="dice-icon dice6"></span>')
        .replace(/\[dic?eClaw\]/ig, '<span class="dice-icon dice6"></span>')
        .replace(/\[dieFateEye\]/ig, '<span class="dice-icon die-of-fate eye"></span>')
        .replace(/\[dieFateRiver\]/ig, '<span class="dice-icon die-of-fate river"></span>')
        .replace(/\[dieFateSnake\]/ig, '<span class="dice-icon die-of-fate snake"></span>')
        .replace(/\[dieFateAnkh\]/ig, '<span class="dice-icon die-of-fate ankh"></span>')
        .replace(/\[berserkDieEnergy\]/ig, '<span class="dice-icon berserk dice1"></span>')
        .replace(/\[berserkDieDoubleEnergy\]/ig, '<span class="dice-icon berserk dice2"></span>')
        .replace(/\[berserkDieSmash\]/ig, '<span class="dice-icon berserk dice3"></span>')
        .replace(/\[berserkDieDoubleSmash\]/ig, '<span class="dice-icon berserk dice5"></span>')
        .replace(/\[berserkDieSkull\]/ig, '<span class="dice-icon berserk dice6"></span>')
        .replace(/\[snowflakeToken\]/ig, '<span class="icy-reflection token"></span>')
        .replace(/\[ufoToken\]/ig, '<span class="ufo token"></span>')
        .replace(/\[alienoidToken\]/ig, '<span class="alienoid token"></span>')
        .replace(/\[targetToken\]/ig, '<span class="target token"></span>')
        .replace(/\[keep\]/ig, "<span class=\"card-keep-text\"><span class=\"outline\">".concat(_('Keep'), "</span><span class=\"text\">").concat(_('Keep'), "</span></span>"))
        .replace(/\[discard\]/ig, "<span class=\"card-discard-text\"><span class=\"outline\">".concat(_('Discard'), "</span><span class=\"text\">").concat(_('Discard'), "</span></span>"));
}
var __spreadArray = (this && this.__spreadArray) || function (to, from, pack) {
    if (pack || arguments.length === 2) for (var i = 0, l = from.length, ar; i < l; i++) {
        if (ar || !(i in from)) {
            if (!ar) ar = Array.prototype.slice.call(from, 0, i);
            ar[i] = from[i];
        }
    }
    return to.concat(ar || Array.prototype.slice.call(from));
};
var DEFAULT_ZOOM_LEVELS = [0.25, 0.375, 0.5, 0.625, 0.75, 0.875, 1];
function throttle(callback, delay) {
    var last;
    var timer;
    return function () {
        var context = this;
        var now = +new Date();
        var args = arguments;
        if (last && now < last + delay) {
            clearTimeout(timer);
            timer = setTimeout(function () {
                last = now;
                callback.apply(context, args);
            }, delay);
        }
        else {
            last = now;
            callback.apply(context, args);
        }
    };
}
var advThrottle = function (func, delay, options) {
    if (options === void 0) { options = { leading: true, trailing: false }; }
    var timer = null, lastRan = null, trailingArgs = null;
    return function () {
        var args = [];
        for (var _i = 0; _i < arguments.length; _i++) {
            args[_i] = arguments[_i];
        }
        if (timer) { //called within cooldown period
            lastRan = this; //update context
            trailingArgs = args; //save for later
            return;
        }
        if (options.leading) { // if leading
            func.call.apply(// if leading
            func, __spreadArray([this], args, false)); //call the 1st instance
        }
        else { // else it's trailing
            lastRan = this; //update context
            trailingArgs = args; //save for later
        }
        var coolDownPeriodComplete = function () {
            if (options.trailing && trailingArgs) { // if trailing and the trailing args exist
                func.call.apply(// if trailing and the trailing args exist
                func, __spreadArray([lastRan], trailingArgs, false)); //invoke the instance with stored context "lastRan"
                lastRan = null; //reset the status of lastRan
                trailingArgs = null; //reset trailing arguments
                timer = setTimeout(coolDownPeriodComplete, delay); //clear the timout
            }
            else {
                timer = null; // reset timer
            }
        };
        timer = setTimeout(coolDownPeriodComplete, delay);
    };
};
var ZoomManager = /** @class */ (function () {
    /**
     * Place the settings.element in a zoom wrapper and init zoomControls.
     *
     * @param settings: a `ZoomManagerSettings` object
     */
    function ZoomManager(settings) {
        var _this = this;
        var _a, _b, _c, _d, _e, _f;
        this.settings = settings;
        if (!settings.element) {
            throw new DOMException('You need to set the element to wrap in the zoom element');
        }
        this._zoomLevels = (_a = settings.zoomLevels) !== null && _a !== void 0 ? _a : DEFAULT_ZOOM_LEVELS;
        this._zoom = this.settings.defaultZoom || 1;
        if (this.settings.localStorageZoomKey) {
            var zoomStr = localStorage.getItem(this.settings.localStorageZoomKey);
            if (zoomStr) {
                this._zoom = Number(zoomStr);
            }
        }
        this.wrapper = document.createElement('div');
        this.wrapper.id = 'bga-zoom-wrapper';
        this.wrapElement(this.wrapper, settings.element);
        this.wrapper.appendChild(settings.element);
        settings.element.classList.add('bga-zoom-inner');
        if ((_b = settings.smooth) !== null && _b !== void 0 ? _b : true) {
            settings.element.dataset.smooth = 'true';
            settings.element.addEventListener('transitionend', advThrottle(function () { return _this.zoomOrDimensionChanged(); }, this.throttleTime, { leading: true, trailing: true, }));
        }
        if ((_d = (_c = settings.zoomControls) === null || _c === void 0 ? void 0 : _c.visible) !== null && _d !== void 0 ? _d : true) {
            this.initZoomControls(settings);
        }
        if (this._zoom !== 1) {
            this.setZoom(this._zoom);
        }
        this.throttleTime = (_e = settings.throttleTime) !== null && _e !== void 0 ? _e : 100;
        window.addEventListener('resize', advThrottle(function () {
            var _a;
            _this.zoomOrDimensionChanged();
            if ((_a = _this.settings.autoZoom) === null || _a === void 0 ? void 0 : _a.expectedWidth) {
                _this.setAutoZoom();
            }
        }, this.throttleTime, { leading: true, trailing: true, }));
        if (window.ResizeObserver) {
            new ResizeObserver(advThrottle(function () { return _this.zoomOrDimensionChanged(); }, this.throttleTime, { leading: true, trailing: true, })).observe(settings.element);
        }
        if ((_f = this.settings.autoZoom) === null || _f === void 0 ? void 0 : _f.expectedWidth) {
            this.setAutoZoom();
        }
    }
    Object.defineProperty(ZoomManager.prototype, "zoom", {
        /**
         * Returns the zoom level
         */
        get: function () {
            return this._zoom;
        },
        enumerable: false,
        configurable: true
    });
    Object.defineProperty(ZoomManager.prototype, "zoomLevels", {
        /**
         * Returns the zoom levels
         */
        get: function () {
            return this._zoomLevels;
        },
        enumerable: false,
        configurable: true
    });
    ZoomManager.prototype.setAutoZoom = function () {
        var _this = this;
        var _a, _b, _c;
        var zoomWrapperWidth = document.getElementById('bga-zoom-wrapper').clientWidth;
        if (!zoomWrapperWidth) {
            setTimeout(function () { return _this.setAutoZoom(); }, 200);
            return;
        }
        var expectedWidth = (_a = this.settings.autoZoom) === null || _a === void 0 ? void 0 : _a.expectedWidth;
        var newZoom = this.zoom;
        while (newZoom > this._zoomLevels[0] && newZoom > ((_c = (_b = this.settings.autoZoom) === null || _b === void 0 ? void 0 : _b.minZoomLevel) !== null && _c !== void 0 ? _c : 0) && zoomWrapperWidth / newZoom < expectedWidth) {
            newZoom = this._zoomLevels[this._zoomLevels.indexOf(newZoom) - 1];
        }
        if (this._zoom == newZoom) {
            if (this.settings.localStorageZoomKey) {
                localStorage.setItem(this.settings.localStorageZoomKey, '' + this._zoom);
            }
        }
        else {
            this.setZoom(newZoom);
        }
    };
    /**
     * Sets the available zoomLevels and new zoom to the provided values.
     * @param zoomLevels the new array of zoomLevels that can be used.
     * @param newZoom if provided the zoom will be set to this value, if not the last element of the zoomLevels array will be set as the new zoom
     */
    ZoomManager.prototype.setZoomLevels = function (zoomLevels, newZoom) {
        if (!zoomLevels || zoomLevels.length <= 0) {
            return;
        }
        this._zoomLevels = zoomLevels;
        var zoomIndex = newZoom && zoomLevels.includes(newZoom) ? this._zoomLevels.indexOf(newZoom) : this._zoomLevels.length - 1;
        this.setZoom(this._zoomLevels[zoomIndex]);
    };
    /**
     * Set the zoom level. Ideally, use a zoom level in the zoomLevels range.
     * @param zoom zool level
     */
    ZoomManager.prototype.setZoom = function (zoom) {
        var _a, _b, _c, _d;
        if (zoom === void 0) { zoom = 1; }
        this._zoom = zoom;
        if (this.settings.localStorageZoomKey) {
            localStorage.setItem(this.settings.localStorageZoomKey, '' + this._zoom);
        }
        var newIndex = this._zoomLevels.indexOf(this._zoom);
        (_a = this.zoomInButton) === null || _a === void 0 ? void 0 : _a.classList.toggle('disabled', newIndex === this._zoomLevels.length - 1);
        (_b = this.zoomOutButton) === null || _b === void 0 ? void 0 : _b.classList.toggle('disabled', newIndex === 0);
        this.settings.element.style.transform = zoom === 1 ? '' : "scale(".concat(zoom, ")");
        (_d = (_c = this.settings).onZoomChange) === null || _d === void 0 ? void 0 : _d.call(_c, this._zoom);
        this.zoomOrDimensionChanged();
    };
    /**
     * Call this method for the browsers not supporting ResizeObserver, everytime the table height changes, if you know it.
     * If the browsert is recent enough (>= Safari 13.1) it will just be ignored.
     */
    ZoomManager.prototype.manualHeightUpdate = function () {
        if (!window.ResizeObserver) {
            this.zoomOrDimensionChanged();
        }
    };
    /**
     * Everytime the element dimensions changes, we update the style. And call the optional callback.
     * Unsafe method as this is not protected by throttle. Surround with  `advThrottle(() => this.zoomOrDimensionChanged(), this.throttleTime, { leading: true, trailing: true, })` to avoid spamming recomputation.
     */
    ZoomManager.prototype.zoomOrDimensionChanged = function () {
        var _a, _b;
        this.settings.element.style.width = "".concat(this.wrapper.offsetWidth / this._zoom, "px");
        this.wrapper.style.height = "".concat(this.settings.element.offsetHeight * this._zoom, "px");
        (_b = (_a = this.settings).onDimensionsChange) === null || _b === void 0 ? void 0 : _b.call(_a, this._zoom);
    };
    /**
     * Simulates a click on the Zoom-in button.
     */
    ZoomManager.prototype.zoomIn = function () {
        if (this._zoom === this._zoomLevels[this._zoomLevels.length - 1]) {
            return;
        }
        var newIndex = this._zoomLevels.indexOf(this._zoom) + 1;
        this.setZoom(newIndex === -1 ? 1 : this._zoomLevels[newIndex]);
    };
    /**
     * Simulates a click on the Zoom-out button.
     */
    ZoomManager.prototype.zoomOut = function () {
        if (this._zoom === this._zoomLevels[0]) {
            return;
        }
        var newIndex = this._zoomLevels.indexOf(this._zoom) - 1;
        this.setZoom(newIndex === -1 ? 1 : this._zoomLevels[newIndex]);
    };
    /**
     * Changes the color of the zoom controls.
     */
    ZoomManager.prototype.setZoomControlsColor = function (color) {
        if (this.zoomControls) {
            this.zoomControls.dataset.color = color;
        }
    };
    /**
     * Set-up the zoom controls
     * @param settings a `ZoomManagerSettings` object.
     */
    ZoomManager.prototype.initZoomControls = function (settings) {
        var _this = this;
        var _a, _b, _c, _d, _e, _f;
        this.zoomControls = document.createElement('div');
        this.zoomControls.id = 'bga-zoom-controls';
        this.zoomControls.dataset.position = (_b = (_a = settings.zoomControls) === null || _a === void 0 ? void 0 : _a.position) !== null && _b !== void 0 ? _b : 'top-right';
        this.zoomOutButton = document.createElement('button');
        this.zoomOutButton.type = 'button';
        this.zoomOutButton.addEventListener('click', function () { return _this.zoomOut(); });
        if ((_c = settings.zoomControls) === null || _c === void 0 ? void 0 : _c.customZoomOutElement) {
            settings.zoomControls.customZoomOutElement(this.zoomOutButton);
        }
        else {
            this.zoomOutButton.classList.add("bga-zoom-out-icon");
        }
        this.zoomInButton = document.createElement('button');
        this.zoomInButton.type = 'button';
        this.zoomInButton.addEventListener('click', function () { return _this.zoomIn(); });
        if ((_d = settings.zoomControls) === null || _d === void 0 ? void 0 : _d.customZoomInElement) {
            settings.zoomControls.customZoomInElement(this.zoomInButton);
        }
        else {
            this.zoomInButton.classList.add("bga-zoom-in-icon");
        }
        this.zoomControls.appendChild(this.zoomOutButton);
        this.zoomControls.appendChild(this.zoomInButton);
        this.wrapper.appendChild(this.zoomControls);
        this.setZoomControlsColor((_f = (_e = settings.zoomControls) === null || _e === void 0 ? void 0 : _e.color) !== null && _f !== void 0 ? _f : 'black');
    };
    /**
     * Wraps an element around an existing DOM element
     * @param wrapper the wrapper element
     * @param element the existing element
     */
    ZoomManager.prototype.wrapElement = function (wrapper, element) {
        element.parentNode.insertBefore(wrapper, element);
        wrapper.appendChild(element);
    };
    return ZoomManager;
}());
var BgaAnimation = /** @class */ (function () {
    function BgaAnimation(animationFunction, settings) {
        this.animationFunction = animationFunction;
        this.settings = settings;
        this.played = null;
        this.result = null;
        this.playWhenNoAnimation = false;
    }
    return BgaAnimation;
}());
var __extends = (this && this.__extends) || (function () {
    var extendStatics = function (d, b) {
        extendStatics = Object.setPrototypeOf ||
            ({ __proto__: [] } instanceof Array && function (d, b) { d.__proto__ = b; }) ||
            function (d, b) { for (var p in b) if (Object.prototype.hasOwnProperty.call(b, p)) d[p] = b[p]; };
        return extendStatics(d, b);
    };
    return function (d, b) {
        if (typeof b !== "function" && b !== null)
            throw new TypeError("Class extends value " + String(b) + " is not a constructor or null");
        extendStatics(d, b);
        function __() { this.constructor = d; }
        d.prototype = b === null ? Object.create(b) : (__.prototype = b.prototype, new __());
    };
})();
/**
 * Just use playSequence from animationManager
 *
 * @param animationManager the animation manager
 * @param animation a `BgaAnimation` object
 * @returns a promise when animation ends
 */
function attachWithAnimation(animationManager, animation) {
    var _a;
    var settings = animation.settings;
    var element = settings.animation.settings.element;
    var fromRect = element.getBoundingClientRect();
    settings.animation.settings.fromRect = fromRect;
    settings.attachElement.appendChild(element);
    (_a = settings.afterAttach) === null || _a === void 0 ? void 0 : _a.call(settings, element, settings.attachElement);
    return animationManager.play(settings.animation);
}
var BgaAttachWithAnimation = /** @class */ (function (_super) {
    __extends(BgaAttachWithAnimation, _super);
    function BgaAttachWithAnimation(settings) {
        var _this = _super.call(this, attachWithAnimation, settings) || this;
        _this.playWhenNoAnimation = true;
        return _this;
    }
    return BgaAttachWithAnimation;
}(BgaAnimation));
/**
 * Just use playSequence from animationManager
 *
 * @param animationManager the animation manager
 * @param animation a `BgaAnimation` object
 * @returns a promise when animation ends
 */
function cumulatedAnimations(animationManager, animation) {
    return animationManager.playSequence(animation.settings.animations);
}
var BgaCumulatedAnimation = /** @class */ (function (_super) {
    __extends(BgaCumulatedAnimation, _super);
    function BgaCumulatedAnimation(settings) {
        var _this = _super.call(this, cumulatedAnimations, settings) || this;
        _this.playWhenNoAnimation = true;
        return _this;
    }
    return BgaCumulatedAnimation;
}(BgaAnimation));
/**
 * Linear slide of the element from origin to destination.
 *
 * @param animationManager the animation manager
 * @param animation a `BgaAnimation` object
 * @returns a promise when animation ends
 */
function slideToAnimation(animationManager, animation) {
    var promise = new Promise(function (success) {
        var _a, _b, _c, _d;
        var settings = animation.settings;
        var element = settings.element;
        var _e = getDeltaCoordinates(element, settings), x = _e.x, y = _e.y;
        var duration = (_a = settings === null || settings === void 0 ? void 0 : settings.duration) !== null && _a !== void 0 ? _a : 500;
        var originalZIndex = element.style.zIndex;
        var originalTransition = element.style.transition;
        element.style.zIndex = "".concat((_b = settings === null || settings === void 0 ? void 0 : settings.zIndex) !== null && _b !== void 0 ? _b : 10);
        var timeoutId = null;
        var cleanOnTransitionEnd = function () {
            element.style.zIndex = originalZIndex;
            element.style.transition = originalTransition;
            success();
            element.removeEventListener('transitioncancel', cleanOnTransitionEnd);
            element.removeEventListener('transitionend', cleanOnTransitionEnd);
            document.removeEventListener('visibilitychange', cleanOnTransitionEnd);
            if (timeoutId) {
                clearTimeout(timeoutId);
            }
        };
        var cleanOnTransitionCancel = function () {
            var _a;
            element.style.transition = "";
            element.offsetHeight;
            element.style.transform = (_a = settings === null || settings === void 0 ? void 0 : settings.finalTransform) !== null && _a !== void 0 ? _a : null;
            element.offsetHeight;
            cleanOnTransitionEnd();
        };
        element.addEventListener('transitioncancel', cleanOnTransitionEnd);
        element.addEventListener('transitionend', cleanOnTransitionEnd);
        document.addEventListener('visibilitychange', cleanOnTransitionCancel);
        element.offsetHeight;
        element.style.transition = "transform ".concat(duration, "ms linear");
        element.offsetHeight;
        element.style.transform = "translate(".concat(-x, "px, ").concat(-y, "px) rotate(").concat((_c = settings === null || settings === void 0 ? void 0 : settings.rotationDelta) !== null && _c !== void 0 ? _c : 0, "deg) scale(").concat((_d = settings.scale) !== null && _d !== void 0 ? _d : 1, ")");
        // safety in case transitionend and transitioncancel are not called
        timeoutId = setTimeout(cleanOnTransitionEnd, duration + 100);
    });
    return promise;
}
var BgaSlideToAnimation = /** @class */ (function (_super) {
    __extends(BgaSlideToAnimation, _super);
    function BgaSlideToAnimation(settings) {
        return _super.call(this, slideToAnimation, settings) || this;
    }
    return BgaSlideToAnimation;
}(BgaAnimation));
/**
 * Linear slide of the element from origin to destination.
 *
 * @param animationManager the animation manager
 * @param animation a `BgaAnimation` object
 * @returns a promise when animation ends
 */
function slideAnimation(animationManager, animation) {
    var promise = new Promise(function (success) {
        var _a, _b, _c, _d;
        var settings = animation.settings;
        var element = settings.element;
        var _e = getDeltaCoordinates(element, settings), x = _e.x, y = _e.y;
        var duration = (_a = settings === null || settings === void 0 ? void 0 : settings.duration) !== null && _a !== void 0 ? _a : 500;
        var originalZIndex = element.style.zIndex;
        var originalTransition = element.style.transition;
        element.style.zIndex = "".concat((_b = settings === null || settings === void 0 ? void 0 : settings.zIndex) !== null && _b !== void 0 ? _b : 10);
        element.style.transition = null;
        element.offsetHeight;
        element.style.transform = "translate(".concat(-x, "px, ").concat(-y, "px) rotate(").concat((_c = settings === null || settings === void 0 ? void 0 : settings.rotationDelta) !== null && _c !== void 0 ? _c : 0, "deg)");
        var timeoutId = null;
        var cleanOnTransitionEnd = function () {
            element.style.zIndex = originalZIndex;
            element.style.transition = originalTransition;
            success();
            element.removeEventListener('transitioncancel', cleanOnTransitionEnd);
            element.removeEventListener('transitionend', cleanOnTransitionEnd);
            document.removeEventListener('visibilitychange', cleanOnTransitionEnd);
            if (timeoutId) {
                clearTimeout(timeoutId);
            }
        };
        var cleanOnTransitionCancel = function () {
            var _a;
            element.style.transition = "";
            element.offsetHeight;
            element.style.transform = (_a = settings === null || settings === void 0 ? void 0 : settings.finalTransform) !== null && _a !== void 0 ? _a : null;
            element.offsetHeight;
            cleanOnTransitionEnd();
        };
        element.addEventListener('transitioncancel', cleanOnTransitionCancel);
        element.addEventListener('transitionend', cleanOnTransitionEnd);
        document.addEventListener('visibilitychange', cleanOnTransitionCancel);
        element.offsetHeight;
        element.style.transition = "transform ".concat(duration, "ms linear");
        element.offsetHeight;
        element.style.transform = (_d = settings === null || settings === void 0 ? void 0 : settings.finalTransform) !== null && _d !== void 0 ? _d : null;
        // safety in case transitionend and transitioncancel are not called
        timeoutId = setTimeout(cleanOnTransitionEnd, duration + 100);
    });
    return promise;
}
var BgaSlideAnimation = /** @class */ (function (_super) {
    __extends(BgaSlideAnimation, _super);
    function BgaSlideAnimation(settings) {
        return _super.call(this, slideAnimation, settings) || this;
    }
    return BgaSlideAnimation;
}(BgaAnimation));
/**
 * Just does nothing for the duration
 *
 * @param animationManager the animation manager
 * @param animation a `BgaAnimation` object
 * @returns a promise when animation ends
 */
function pauseAnimation(animationManager, animation) {
    var promise = new Promise(function (success) {
        var _a;
        var settings = animation.settings;
        var duration = (_a = settings === null || settings === void 0 ? void 0 : settings.duration) !== null && _a !== void 0 ? _a : 500;
        setTimeout(function () { return success(); }, duration);
    });
    return promise;
}
var BgaPauseAnimation = /** @class */ (function (_super) {
    __extends(BgaPauseAnimation, _super);
    function BgaPauseAnimation(settings) {
        return _super.call(this, pauseAnimation, settings) || this;
    }
    return BgaPauseAnimation;
}(BgaAnimation));
function shouldAnimate(settings) {
    var _a;
    return document.visibilityState !== 'hidden' && !((_a = settings === null || settings === void 0 ? void 0 : settings.game) === null || _a === void 0 ? void 0 : _a.instantaneousMode);
}
/**
 * Return the x and y delta, based on the animation settings;
 *
 * @param settings an `AnimationSettings` object
 * @returns a promise when animation ends
 */
function getDeltaCoordinates(element, settings) {
    var _a;
    if (!settings.fromDelta && !settings.fromRect && !settings.fromElement) {
        throw new Error("[bga-animation] fromDelta, fromRect or fromElement need to be set");
    }
    var x = 0;
    var y = 0;
    if (settings.fromDelta) {
        x = settings.fromDelta.x;
        y = settings.fromDelta.y;
    }
    else {
        var originBR = (_a = settings.fromRect) !== null && _a !== void 0 ? _a : settings.fromElement.getBoundingClientRect();
        // TODO make it an option ?
        var originalTransform = element.style.transform;
        element.style.transform = '';
        var destinationBR = element.getBoundingClientRect();
        element.style.transform = originalTransform;
        x = (destinationBR.left + destinationBR.right) / 2 - (originBR.left + originBR.right) / 2;
        y = (destinationBR.top + destinationBR.bottom) / 2 - (originBR.top + originBR.bottom) / 2;
    }
    if (settings.scale) {
        x /= settings.scale;
        y /= settings.scale;
    }
    return { x: x, y: y };
}
function logAnimation(animationManager, animation) {
    var settings = animation.settings;
    var element = settings.element;
    if (element) {
        console.log(animation, settings, element, element.getBoundingClientRect(), element.style.transform);
    }
    else {
        console.log(animation, settings);
    }
    return Promise.resolve(false);
}
var __assign = (this && this.__assign) || function () {
    __assign = Object.assign || function(t) {
        for (var s, i = 1, n = arguments.length; i < n; i++) {
            s = arguments[i];
            for (var p in s) if (Object.prototype.hasOwnProperty.call(s, p))
                t[p] = s[p];
        }
        return t;
    };
    return __assign.apply(this, arguments);
};
var __awaiter = (this && this.__awaiter) || function (thisArg, _arguments, P, generator) {
    function adopt(value) { return value instanceof P ? value : new P(function (resolve) { resolve(value); }); }
    return new (P || (P = Promise))(function (resolve, reject) {
        function fulfilled(value) { try { step(generator.next(value)); } catch (e) { reject(e); } }
        function rejected(value) { try { step(generator["throw"](value)); } catch (e) { reject(e); } }
        function step(result) { result.done ? resolve(result.value) : adopt(result.value).then(fulfilled, rejected); }
        step((generator = generator.apply(thisArg, _arguments || [])).next());
    });
};
var __generator = (this && this.__generator) || function (thisArg, body) {
    var _ = { label: 0, sent: function() { if (t[0] & 1) throw t[1]; return t[1]; }, trys: [], ops: [] }, f, y, t, g;
    return g = { next: verb(0), "throw": verb(1), "return": verb(2) }, typeof Symbol === "function" && (g[Symbol.iterator] = function() { return this; }), g;
    function verb(n) { return function (v) { return step([n, v]); }; }
    function step(op) {
        if (f) throw new TypeError("Generator is already executing.");
        while (g && (g = 0, op[0] && (_ = 0)), _) try {
            if (f = 1, y && (t = op[0] & 2 ? y["return"] : op[0] ? y["throw"] || ((t = y["return"]) && t.call(y), 0) : y.next) && !(t = t.call(y, op[1])).done) return t;
            if (y = 0, t) op = [op[0] & 2, t.value];
            switch (op[0]) {
                case 0: case 1: t = op; break;
                case 4: _.label++; return { value: op[1], done: false };
                case 5: _.label++; y = op[1]; op = [0]; continue;
                case 7: op = _.ops.pop(); _.trys.pop(); continue;
                default:
                    if (!(t = _.trys, t = t.length > 0 && t[t.length - 1]) && (op[0] === 6 || op[0] === 2)) { _ = 0; continue; }
                    if (op[0] === 3 && (!t || (op[1] > t[0] && op[1] < t[3]))) { _.label = op[1]; break; }
                    if (op[0] === 6 && _.label < t[1]) { _.label = t[1]; t = op; break; }
                    if (t && _.label < t[2]) { _.label = t[2]; _.ops.push(op); break; }
                    if (t[2]) _.ops.pop();
                    _.trys.pop(); continue;
            }
            op = body.call(thisArg, _);
        } catch (e) { op = [6, e]; y = 0; } finally { f = t = 0; }
        if (op[0] & 5) throw op[1]; return { value: op[0] ? op[1] : void 0, done: true };
    }
};
var AnimationManager = /** @class */ (function () {
    /**
     * @param game the BGA game class, usually it will be `this`
     * @param settings: a `AnimationManagerSettings` object
     */
    function AnimationManager(game, settings) {
        this.game = game;
        this.settings = settings;
        this.zoomManager = settings === null || settings === void 0 ? void 0 : settings.zoomManager;
        if (!game) {
            throw new Error('You must set your game as the first parameter of AnimationManager');
        }
    }
    AnimationManager.prototype.getZoomManager = function () {
        return this.zoomManager;
    };
    /**
     * Set the zoom manager, to get the scale of the current game.
     *
     * @param zoomManager the zoom manager
     */
    AnimationManager.prototype.setZoomManager = function (zoomManager) {
        this.zoomManager = zoomManager;
    };
    AnimationManager.prototype.getSettings = function () {
        return this.settings;
    };
    /**
     * Returns if the animations are active. Animation aren't active when the window is not visible (`document.visibilityState === 'hidden'`), or `game.instantaneousMode` is true.
     *
     * @returns if the animations are active.
     */
    AnimationManager.prototype.animationsActive = function () {
        return document.visibilityState !== 'hidden' && !this.game.instantaneousMode;
    };
    /**
     * Plays an animation if the animations are active. Animation aren't active when the window is not visible (`document.visibilityState === 'hidden'`), or `game.instantaneousMode` is true.
     *
     * @param animation the animation to play
     * @returns the animation promise.
     */
    AnimationManager.prototype.play = function (animation) {
        return __awaiter(this, void 0, void 0, function () {
            var settings, _a;
            var _b, _c, _d, _e, _f, _g, _h, _j, _k, _l, _m;
            return __generator(this, function (_o) {
                switch (_o.label) {
                    case 0:
                        animation.played = animation.playWhenNoAnimation || this.animationsActive();
                        if (!animation.played) return [3 /*break*/, 2];
                        settings = animation.settings;
                        (_b = settings.animationStart) === null || _b === void 0 ? void 0 : _b.call(settings, animation);
                        (_c = settings.element) === null || _c === void 0 ? void 0 : _c.classList.add((_d = settings.animationClass) !== null && _d !== void 0 ? _d : 'bga-animations_animated');
                        animation.settings = __assign(__assign({}, animation.settings), { duration: (_f = (_e = this.settings) === null || _e === void 0 ? void 0 : _e.duration) !== null && _f !== void 0 ? _f : 500, scale: (_h = (_g = this.zoomManager) === null || _g === void 0 ? void 0 : _g.zoom) !== null && _h !== void 0 ? _h : undefined });
                        _a = animation;
                        return [4 /*yield*/, animation.animationFunction(this, animation)];
                    case 1:
                        _a.result = _o.sent();
                        (_k = (_j = animation.settings).animationEnd) === null || _k === void 0 ? void 0 : _k.call(_j, animation);
                        (_l = settings.element) === null || _l === void 0 ? void 0 : _l.classList.remove((_m = settings.animationClass) !== null && _m !== void 0 ? _m : 'bga-animations_animated');
                        return [3 /*break*/, 3];
                    case 2: return [2 /*return*/, Promise.resolve(animation)];
                    case 3: return [2 /*return*/];
                }
            });
        });
    };
    /**
     * Plays multiple animations in parallel.
     *
     * @param animations the animations to play
     * @returns a promise for all animations.
     */
    AnimationManager.prototype.playParallel = function (animations) {
        return __awaiter(this, void 0, void 0, function () {
            var _this = this;
            return __generator(this, function (_a) {
                return [2 /*return*/, Promise.all(animations.map(function (animation) { return _this.play(animation); }))];
            });
        });
    };
    /**
     * Plays multiple animations in sequence (the second when the first ends, ...).
     *
     * @param animations the animations to play
     * @returns a promise for all animations.
     */
    AnimationManager.prototype.playSequence = function (animations) {
        return __awaiter(this, void 0, void 0, function () {
            var result, others;
            return __generator(this, function (_a) {
                switch (_a.label) {
                    case 0:
                        if (!animations.length) return [3 /*break*/, 3];
                        return [4 /*yield*/, this.play(animations[0])];
                    case 1:
                        result = _a.sent();
                        return [4 /*yield*/, this.playSequence(animations.slice(1))];
                    case 2:
                        others = _a.sent();
                        return [2 /*return*/, __spreadArray([result], others, true)];
                    case 3: return [2 /*return*/, Promise.resolve([])];
                }
            });
        });
    };
    /**
     * Plays multiple animations with a delay between each animation start.
     *
     * @param animations the animations to play
     * @param delay the delay (in ms)
     * @returns a promise for all animations.
     */
    AnimationManager.prototype.playWithDelay = function (animations, delay) {
        return __awaiter(this, void 0, void 0, function () {
            var promise;
            var _this = this;
            return __generator(this, function (_a) {
                promise = new Promise(function (success) {
                    var promises = [];
                    var _loop_1 = function (i) {
                        setTimeout(function () {
                            promises.push(_this.play(animations[i]));
                            if (i == animations.length - 1) {
                                Promise.all(promises).then(function (result) {
                                    success(result);
                                });
                            }
                        }, i * delay);
                    };
                    for (var i = 0; i < animations.length; i++) {
                        _loop_1(i);
                    }
                });
                return [2 /*return*/, promise];
            });
        });
    };
    /**
     * Attach an element to a parent, then play animation from element's origin to its new position.
     *
     * @param animation the animation function
     * @param attachElement the destination parent
     * @returns a promise when animation ends
     */
    AnimationManager.prototype.attachWithAnimation = function (animation, attachElement) {
        var attachWithAnimation = new BgaAttachWithAnimation({
            animation: animation,
            attachElement: attachElement
        });
        return this.play(attachWithAnimation);
    };
    return AnimationManager;
}());
function sortFunction() {
    var sortedFields = [];
    for (var _i = 0; _i < arguments.length; _i++) {
        sortedFields[_i] = arguments[_i];
    }
    return function (a, b) {
        for (var i = 0; i < sortedFields.length; i++) {
            var direction = 1;
            var field = sortedFields[i];
            if (field[0] == '-') {
                direction = -1;
                field = field.substring(1);
            }
            else if (field[0] == '+') {
                field = field.substring(1);
            }
            var type = typeof a[field];
            if (type === 'string') {
                var compare = a[field].localeCompare(b[field]);
                if (compare !== 0) {
                    return compare;
                }
            }
            else if (type === 'number') {
                var compare = (a[field] - b[field]) * direction;
                if (compare !== 0) {
                    return compare * direction;
                }
            }
        }
        return 0;
    };
}
/**
 * The abstract stock. It shouldn't be used directly, use stocks that extends it.
 */
var CardStock = /** @class */ (function () {
    /**
     * Creates the stock and register it on the manager.
     *
     * @param manager the card manager
     * @param element the stock element (should be an empty HTML Element)
     */
    function CardStock(manager, element, settings) {
        this.manager = manager;
        this.element = element;
        this.settings = settings;
        this.cards = [];
        this.selectedCards = [];
        this.selectionMode = 'none';
        manager.addStock(this);
        element === null || element === void 0 ? void 0 : element.classList.add('card-stock' /*, this.constructor.name.split(/(?=[A-Z])/).join('-').toLowerCase()* doesn't work in production because of minification */);
        this.bindClick();
        this.sort = settings === null || settings === void 0 ? void 0 : settings.sort;
    }
    /**
     * Removes the stock and unregister it on the manager.
     */
    CardStock.prototype.remove = function () {
        var _a;
        this.manager.removeStock(this);
        (_a = this.element) === null || _a === void 0 ? void 0 : _a.remove();
    };
    /**
     * @returns the cards on the stock
     */
    CardStock.prototype.getCards = function () {
        return this.cards.slice();
    };
    /**
     * @returns if the stock is empty
     */
    CardStock.prototype.isEmpty = function () {
        return !this.cards.length;
    };
    /**
     * @returns the selected cards
     */
    CardStock.prototype.getSelection = function () {
        return this.selectedCards.slice();
    };
    /**
     * @returns the selected cards
     */
    CardStock.prototype.isSelected = function (card) {
        var _this = this;
        return this.selectedCards.some(function (c) { return _this.manager.getId(c) == _this.manager.getId(card); });
    };
    /**
     * @param card a card
     * @returns if the card is present in the stock
     */
    CardStock.prototype.contains = function (card) {
        var _this = this;
        return this.cards.some(function (c) { return _this.manager.getId(c) == _this.manager.getId(card); });
    };
    /**
     * @param card a card in the stock
     * @returns the HTML element generated for the card
     */
    CardStock.prototype.getCardElement = function (card) {
        return this.manager.getCardElement(card);
    };
    /**
     * Checks if the card can be added. By default, only if it isn't already present in the stock.
     *
     * @param card the card to add
     * @param settings the addCard settings
     * @returns if the card can be added
     */
    CardStock.prototype.canAddCard = function (card, settings) {
        return !this.contains(card);
    };
    /**
     * Add a card to the stock.
     *
     * @param card the card to add
     * @param animation a `CardAnimation` object
     * @param settings a `AddCardSettings` object
     * @returns the promise when the animation is done (true if it was animated, false if it wasn't)
     */
    CardStock.prototype.addCard = function (card, animation, settings) {
        var _this = this;
        var _a, _b, _c, _d, _e;
        if (!this.canAddCard(card, settings)) {
            return Promise.resolve(false);
        }
        var promise;
        // we check if card is in a stock
        var originStock = this.manager.getCardStock(card);
        var index = this.getNewCardIndex(card);
        var settingsWithIndex = __assign({ index: index }, (settings !== null && settings !== void 0 ? settings : {}));
        var updateInformations = (_a = settingsWithIndex.updateInformations) !== null && _a !== void 0 ? _a : true;
        var needsCreation = true;
        if (originStock === null || originStock === void 0 ? void 0 : originStock.contains(card)) {
            var element = this.getCardElement(card);
            if (element) {
                promise = this.moveFromOtherStock(card, element, __assign(__assign({}, animation), { fromStock: originStock }), settingsWithIndex);
                needsCreation = false;
                if (!updateInformations) {
                    element.dataset.side = ((_b = settingsWithIndex === null || settingsWithIndex === void 0 ? void 0 : settingsWithIndex.visible) !== null && _b !== void 0 ? _b : this.manager.isCardVisible(card)) ? 'front' : 'back';
                }
            }
        }
        else if ((_c = animation === null || animation === void 0 ? void 0 : animation.fromStock) === null || _c === void 0 ? void 0 : _c.contains(card)) {
            var element = this.getCardElement(card);
            if (element) {
                promise = this.moveFromOtherStock(card, element, animation, settingsWithIndex);
                needsCreation = false;
            }
        }
        if (needsCreation) {
            var element = this.getCardElement(card);
            if (needsCreation && element) {
                console.warn("Card ".concat(this.manager.getId(card), " already exists, not re-created."));
            }
            // if the card comes from a stock but is not found in this stock, the card is probably hudden (deck with a fake top card)
            var fromBackSide = !(settingsWithIndex === null || settingsWithIndex === void 0 ? void 0 : settingsWithIndex.visible) && !(animation === null || animation === void 0 ? void 0 : animation.originalSide) && (animation === null || animation === void 0 ? void 0 : animation.fromStock) && !((_d = animation === null || animation === void 0 ? void 0 : animation.fromStock) === null || _d === void 0 ? void 0 : _d.contains(card));
            var createdVisible = fromBackSide ? false : (_e = settingsWithIndex === null || settingsWithIndex === void 0 ? void 0 : settingsWithIndex.visible) !== null && _e !== void 0 ? _e : this.manager.isCardVisible(card);
            var newElement = element !== null && element !== void 0 ? element : this.manager.createCardElement(card, createdVisible);
            promise = this.moveFromElement(card, newElement, animation, settingsWithIndex);
        }
        if (settingsWithIndex.index !== null && settingsWithIndex.index !== undefined) {
            this.cards.splice(index, 0, card);
        }
        else {
            this.cards.push(card);
        }
        if (updateInformations) { // after splice/push
            this.manager.updateCardInformations(card);
        }
        if (!promise) {
            console.warn("CardStock.addCard didn't return a Promise");
            promise = Promise.resolve(false);
        }
        if (this.selectionMode !== 'none') {
            // make selectable only at the end of the animation
            promise.then(function () { var _a; return _this.setSelectableCard(card, (_a = settingsWithIndex.selectable) !== null && _a !== void 0 ? _a : true); });
        }
        return promise;
    };
    CardStock.prototype.getNewCardIndex = function (card) {
        if (this.sort) {
            var otherCards = this.getCards();
            for (var i = 0; i < otherCards.length; i++) {
                var otherCard = otherCards[i];
                if (this.sort(card, otherCard) < 0) {
                    return i;
                }
            }
            return otherCards.length;
        }
        else {
            return undefined;
        }
    };
    CardStock.prototype.addCardElementToParent = function (cardElement, settings) {
        var _a;
        var parent = (_a = settings === null || settings === void 0 ? void 0 : settings.forceToElement) !== null && _a !== void 0 ? _a : this.element;
        if ((settings === null || settings === void 0 ? void 0 : settings.index) === null || (settings === null || settings === void 0 ? void 0 : settings.index) === undefined || !parent.children.length || (settings === null || settings === void 0 ? void 0 : settings.index) >= parent.children.length) {
            parent.appendChild(cardElement);
        }
        else {
            parent.insertBefore(cardElement, parent.children[settings.index]);
        }
    };
    CardStock.prototype.moveFromOtherStock = function (card, cardElement, animation, settings) {
        var promise;
        var element = animation.fromStock.contains(card) ? this.manager.getCardElement(card) : animation.fromStock.element;
        var fromRect = element === null || element === void 0 ? void 0 : element.getBoundingClientRect();
        this.addCardElementToParent(cardElement, settings);
        this.removeSelectionClassesFromElement(cardElement);
        promise = fromRect ? this.animationFromElement(cardElement, fromRect, {
            originalSide: animation.originalSide,
            rotationDelta: animation.rotationDelta,
            animation: animation.animation,
        }) : Promise.resolve(false);
        // in the case the card was move inside the same stock we don't remove it
        if (animation.fromStock && animation.fromStock != this) {
            animation.fromStock.removeCard(card);
        }
        if (!promise) {
            console.warn("CardStock.moveFromOtherStock didn't return a Promise");
            promise = Promise.resolve(false);
        }
        return promise;
    };
    CardStock.prototype.moveFromElement = function (card, cardElement, animation, settings) {
        var promise;
        this.addCardElementToParent(cardElement, settings);
        if (animation) {
            if (animation.fromStock) {
                promise = this.animationFromElement(cardElement, animation.fromStock.element.getBoundingClientRect(), {
                    originalSide: animation.originalSide,
                    rotationDelta: animation.rotationDelta,
                    animation: animation.animation,
                });
                animation.fromStock.removeCard(card);
            }
            else if (animation.fromElement) {
                promise = this.animationFromElement(cardElement, animation.fromElement.getBoundingClientRect(), {
                    originalSide: animation.originalSide,
                    rotationDelta: animation.rotationDelta,
                    animation: animation.animation,
                });
            }
        }
        else {
            promise = Promise.resolve(false);
        }
        if (!promise) {
            console.warn("CardStock.moveFromElement didn't return a Promise");
            promise = Promise.resolve(false);
        }
        return promise;
    };
    /**
     * Add an array of cards to the stock.
     *
     * @param cards the cards to add
     * @param animation a `CardAnimation` object
     * @param settings a `AddCardSettings` object
     * @param shift if number, the number of milliseconds between each card. if true, chain animations
     */
    CardStock.prototype.addCards = function (cards_1, animation_1, settings_1) {
        return __awaiter(this, arguments, void 0, function (cards, animation, settings, shift) {
            var promises, result, others, _loop_2, i, results;
            var _this = this;
            if (shift === void 0) { shift = false; }
            return __generator(this, function (_a) {
                switch (_a.label) {
                    case 0:
                        if (!this.manager.animationsActive()) {
                            shift = false;
                        }
                        promises = [];
                        if (!(shift === true)) return [3 /*break*/, 4];
                        if (!cards.length) return [3 /*break*/, 3];
                        return [4 /*yield*/, this.addCard(cards[0], animation, settings)];
                    case 1:
                        result = _a.sent();
                        return [4 /*yield*/, this.addCards(cards.slice(1), animation, settings, shift)];
                    case 2:
                        others = _a.sent();
                        return [2 /*return*/, result || others];
                    case 3: return [3 /*break*/, 5];
                    case 4:
                        if (typeof shift === 'number') {
                            _loop_2 = function (i) {
                                promises.push(new Promise(function (resolve) {
                                    setTimeout(function () { return _this.addCard(cards[i], animation, settings).then(function (result) { return resolve(result); }); }, i * shift);
                                }));
                            };
                            for (i = 0; i < cards.length; i++) {
                                _loop_2(i);
                            }
                        }
                        else {
                            promises = cards.map(function (card) { return _this.addCard(card, animation, settings); });
                        }
                        _a.label = 5;
                    case 5: return [4 /*yield*/, Promise.all(promises)];
                    case 6:
                        results = _a.sent();
                        return [2 /*return*/, results.some(function (result) { return result; })];
                }
            });
        });
    };
    /**
     * Remove a card from the stock.
     *
     * @param card the card to remove
     * @param settings a `RemoveCardSettings` object
     */
    CardStock.prototype.removeCard = function (card, settings) {
        var promise;
        if (this.contains(card) && this.element.contains(this.getCardElement(card))) {
            promise = this.manager.removeCard(card, settings);
        }
        else {
            promise = Promise.resolve(false);
        }
        this.cardRemoved(card, settings);
        return promise;
    };
    /**
     * Notify the stock that a card is removed.
     *
     * @param card the card to remove
     * @param settings a `RemoveCardSettings` object
     */
    CardStock.prototype.cardRemoved = function (card, settings) {
        var _this = this;
        var index = this.cards.findIndex(function (c) { return _this.manager.getId(c) == _this.manager.getId(card); });
        if (index !== -1) {
            this.cards.splice(index, 1);
        }
        if (this.selectedCards.find(function (c) { return _this.manager.getId(c) == _this.manager.getId(card); })) {
            this.unselectCard(card);
        }
    };
    /**
     * Remove a set of card from the stock.
     *
     * @param cards the cards to remove
     * @param settings a `RemoveCardSettings` object
     */
    CardStock.prototype.removeCards = function (cards, settings) {
        return __awaiter(this, void 0, void 0, function () {
            var promises, results;
            var _this = this;
            return __generator(this, function (_a) {
                switch (_a.label) {
                    case 0:
                        promises = cards.map(function (card) { return _this.removeCard(card, settings); });
                        return [4 /*yield*/, Promise.all(promises)];
                    case 1:
                        results = _a.sent();
                        return [2 /*return*/, results.some(function (result) { return result; })];
                }
            });
        });
    };
    /**
     * Remove all cards from the stock.
     * @param settings a `RemoveCardSettings` object
     */
    CardStock.prototype.removeAll = function (settings) {
        return __awaiter(this, void 0, void 0, function () {
            var cards;
            return __generator(this, function (_a) {
                cards = this.getCards();
                return [2 /*return*/, this.removeCards(cards, settings)];
            });
        });
    };
    /**
     * Set if the stock is selectable, and if yes if it can be multiple.
     * If set to 'none', it will unselect all selected cards.
     *
     * @param selectionMode the selection mode
     * @param selectableCards the selectable cards (all if unset). Calls `setSelectableCards` method
     */
    CardStock.prototype.setSelectionMode = function (selectionMode, selectableCards) {
        var _this = this;
        if (selectionMode !== this.selectionMode) {
            this.unselectAll(true);
        }
        this.cards.forEach(function (card) { return _this.setSelectableCard(card, selectionMode != 'none'); });
        this.element.classList.toggle('bga-cards_selectable-stock', selectionMode != 'none');
        this.selectionMode = selectionMode;
        if (selectionMode === 'none') {
            this.getCards().forEach(function (card) { return _this.removeSelectionClasses(card); });
        }
        else {
            this.setSelectableCards(selectableCards !== null && selectableCards !== void 0 ? selectableCards : this.getCards());
        }
    };
    CardStock.prototype.setSelectableCard = function (card, selectable) {
        if (this.selectionMode === 'none') {
            return;
        }
        var element = this.getCardElement(card);
        var selectableCardsClass = this.getSelectableCardClass();
        var unselectableCardsClass = this.getUnselectableCardClass();
        if (selectableCardsClass) {
            element === null || element === void 0 ? void 0 : element.classList.toggle(selectableCardsClass, selectable);
        }
        if (unselectableCardsClass) {
            element === null || element === void 0 ? void 0 : element.classList.toggle(unselectableCardsClass, !selectable);
        }
        if (!selectable && this.isSelected(card)) {
            this.unselectCard(card, true);
        }
    };
    /**
     * Set the selectable class for each card.
     *
     * @param selectableCards the selectable cards. If unset, all cards are marked selectable. Default unset.
     */
    CardStock.prototype.setSelectableCards = function (selectableCards) {
        var _this = this;
        if (this.selectionMode === 'none') {
            return;
        }
        var selectableCardsIds = (selectableCards !== null && selectableCards !== void 0 ? selectableCards : this.getCards()).map(function (card) { return _this.manager.getId(card); });
        this.cards.forEach(function (card) {
            return _this.setSelectableCard(card, selectableCardsIds.includes(_this.manager.getId(card)));
        });
    };
    /**
     * Set selected state to a card.
     *
     * @param card the card to select
     */
    CardStock.prototype.selectCard = function (card, silent) {
        var _this = this;
        var _a;
        if (silent === void 0) { silent = false; }
        if (this.selectionMode == 'none') {
            return;
        }
        var element = this.getCardElement(card);
        var selectableCardsClass = this.getSelectableCardClass();
        if (!element || !element.classList.contains(selectableCardsClass)) {
            return;
        }
        if (this.selectionMode === 'single') {
            this.cards.filter(function (c) { return _this.manager.getId(c) != _this.manager.getId(card); }).forEach(function (c) { return _this.unselectCard(c, true); });
        }
        var selectedCardsClass = this.getSelectedCardClass();
        element.classList.add(selectedCardsClass);
        this.selectedCards.push(card);
        if (!silent) {
            (_a = this.onSelectionChange) === null || _a === void 0 ? void 0 : _a.call(this, this.selectedCards.slice(), card);
        }
    };
    /**
     * Set unselected state to a card.
     *
     * @param card the card to unselect
     */
    CardStock.prototype.unselectCard = function (card, silent) {
        var _this = this;
        var _a;
        if (silent === void 0) { silent = false; }
        var element = this.getCardElement(card);
        var selectedCardsClass = this.getSelectedCardClass();
        element === null || element === void 0 ? void 0 : element.classList.remove(selectedCardsClass);
        var index = this.selectedCards.findIndex(function (c) { return _this.manager.getId(c) == _this.manager.getId(card); });
        if (index !== -1) {
            this.selectedCards.splice(index, 1);
        }
        if (!silent) {
            (_a = this.onSelectionChange) === null || _a === void 0 ? void 0 : _a.call(this, this.selectedCards.slice(), card);
        }
    };
    /**
     * Select all cards
     */
    CardStock.prototype.selectAll = function (silent) {
        var _this = this;
        var _a;
        if (silent === void 0) { silent = false; }
        if (this.selectionMode == 'none') {
            return;
        }
        this.cards.forEach(function (c) { return _this.selectCard(c, true); });
        if (!silent) {
            (_a = this.onSelectionChange) === null || _a === void 0 ? void 0 : _a.call(this, this.selectedCards.slice(), null);
        }
    };
    /**
     * Unelect all cards
     */
    CardStock.prototype.unselectAll = function (silent) {
        var _this = this;
        var _a;
        if (silent === void 0) { silent = false; }
        var cards = this.getCards(); // use a copy of the array as we iterate and modify it at the same time
        cards.forEach(function (c) { return _this.unselectCard(c, true); });
        if (!silent) {
            (_a = this.onSelectionChange) === null || _a === void 0 ? void 0 : _a.call(this, this.selectedCards.slice(), null);
        }
    };
    CardStock.prototype.bindClick = function () {
        var _this = this;
        var _a;
        (_a = this.element) === null || _a === void 0 ? void 0 : _a.addEventListener('click', function (event) {
            var cardDiv = event.target.closest('.card');
            if (!cardDiv) {
                return;
            }
            var card = _this.cards.find(function (c) { return _this.manager.getId(c) == cardDiv.id; });
            if (!card) {
                return;
            }
            _this.cardClick(card);
        });
    };
    CardStock.prototype.cardClick = function (card) {
        var _this = this;
        var _a;
        if (this.selectionMode != 'none') {
            var alreadySelected = this.selectedCards.some(function (c) { return _this.manager.getId(c) == _this.manager.getId(card); });
            if (alreadySelected) {
                this.unselectCard(card);
            }
            else {
                this.selectCard(card);
            }
        }
        (_a = this.onCardClick) === null || _a === void 0 ? void 0 : _a.call(this, card);
    };
    /**
     * @param element The element to animate. The element is added to the destination stock before the animation starts.
     * @param fromElement The HTMLElement to animate from.
     */
    CardStock.prototype.animationFromElement = function (element, fromRect, settings) {
        return __awaiter(this, void 0, void 0, function () {
            var side, cardSides_1, animation, result;
            var _a;
            return __generator(this, function (_b) {
                switch (_b.label) {
                    case 0:
                        side = element.dataset.side;
                        if (settings.originalSide && settings.originalSide != side) {
                            cardSides_1 = element.getElementsByClassName('card-sides')[0];
                            cardSides_1.style.transition = 'none';
                            element.dataset.side = settings.originalSide;
                            setTimeout(function () {
                                cardSides_1.style.transition = null;
                                element.dataset.side = side;
                            });
                        }
                        animation = settings.animation;
                        if (animation) {
                            animation.settings.element = element;
                            animation.settings.fromRect = fromRect;
                        }
                        else {
                            animation = new BgaSlideAnimation({ element: element, fromRect: fromRect });
                        }
                        return [4 /*yield*/, this.manager.animationManager.play(animation)];
                    case 1:
                        result = _b.sent();
                        return [2 /*return*/, (_a = result === null || result === void 0 ? void 0 : result.played) !== null && _a !== void 0 ? _a : false];
                }
            });
        });
    };
    /**
     * Set the card to its front (visible) or back (not visible) side.
     *
     * @param card the card informations
     */
    CardStock.prototype.setCardVisible = function (card, visible, settings) {
        this.manager.setCardVisible(card, visible, settings);
    };
    /**
     * Flips the card.
     *
     * @param card the card informations
     */
    CardStock.prototype.flipCard = function (card, settings) {
        this.manager.flipCard(card, settings);
    };
    /**
     * @returns the class to apply to selectable cards. Use class from manager is unset.
     */
    CardStock.prototype.getSelectableCardClass = function () {
        var _a, _b;
        return ((_a = this.settings) === null || _a === void 0 ? void 0 : _a.selectableCardClass) === undefined ? this.manager.getSelectableCardClass() : (_b = this.settings) === null || _b === void 0 ? void 0 : _b.selectableCardClass;
    };
    /**
     * @returns the class to apply to selectable cards. Use class from manager is unset.
     */
    CardStock.prototype.getUnselectableCardClass = function () {
        var _a, _b;
        return ((_a = this.settings) === null || _a === void 0 ? void 0 : _a.unselectableCardClass) === undefined ? this.manager.getUnselectableCardClass() : (_b = this.settings) === null || _b === void 0 ? void 0 : _b.unselectableCardClass;
    };
    /**
     * @returns the class to apply to selected cards. Use class from manager is unset.
     */
    CardStock.prototype.getSelectedCardClass = function () {
        var _a, _b;
        return ((_a = this.settings) === null || _a === void 0 ? void 0 : _a.selectedCardClass) === undefined ? this.manager.getSelectedCardClass() : (_b = this.settings) === null || _b === void 0 ? void 0 : _b.selectedCardClass;
    };
    CardStock.prototype.removeSelectionClasses = function (card) {
        this.removeSelectionClassesFromElement(this.getCardElement(card));
    };
    CardStock.prototype.removeSelectionClassesFromElement = function (cardElement) {
        var selectableCardsClass = this.getSelectableCardClass();
        var unselectableCardsClass = this.getUnselectableCardClass();
        var selectedCardsClass = this.getSelectedCardClass();
        cardElement === null || cardElement === void 0 ? void 0 : cardElement.classList.remove(selectableCardsClass, unselectableCardsClass, selectedCardsClass);
    };
    return CardStock;
}());
var SlideAndBackAnimation = /** @class */ (function (_super) {
    __extends(SlideAndBackAnimation, _super);
    function SlideAndBackAnimation(manager, element, tempElement) {
        var distance = (manager.getCardWidth() + manager.getCardHeight()) / 2;
        var angle = Math.random() * Math.PI * 2;
        var fromDelta = {
            x: distance * Math.cos(angle),
            y: distance * Math.sin(angle),
        };
        return _super.call(this, {
            animations: [
                new BgaSlideToAnimation({ element: element, fromDelta: fromDelta, duration: 250 }),
                new BgaSlideAnimation({ element: element, fromDelta: fromDelta, duration: 250, animationEnd: tempElement ? (function () { return element.remove(); }) : undefined }),
            ]
        }) || this;
    }
    return SlideAndBackAnimation;
}(BgaCumulatedAnimation));
/**
 * Abstract stock to represent a deck. (pile of cards, with a fake 3d effect of thickness). *
 * Needs cardWidth and cardHeight to be set in the card manager.
 */
var Deck = /** @class */ (function (_super) {
    __extends(Deck, _super);
    function Deck(manager, element, settings) {
        var _a, _b, _c, _d, _e, _f, _g, _h, _j, _k, _l;
        var _this = _super.call(this, manager, element) || this;
        _this.manager = manager;
        _this.element = element;
        element.classList.add('deck');
        var cardWidth = _this.manager.getCardWidth();
        var cardHeight = _this.manager.getCardHeight();
        if (cardWidth && cardHeight) {
            _this.element.style.setProperty('--width', "".concat(cardWidth, "px"));
            _this.element.style.setProperty('--height', "".concat(cardHeight, "px"));
        }
        else {
            throw new Error("You need to set cardWidth and cardHeight in the card manager to use Deck.");
        }
        _this.fakeCardGenerator = (_a = settings === null || settings === void 0 ? void 0 : settings.fakeCardGenerator) !== null && _a !== void 0 ? _a : manager.getFakeCardGenerator();
        _this.thicknesses = (_b = settings.thicknesses) !== null && _b !== void 0 ? _b : [0, 2, 5, 10, 20, 30];
        _this.setCardNumber((_c = settings.cardNumber) !== null && _c !== void 0 ? _c : 0);
        _this.autoUpdateCardNumber = (_d = settings.autoUpdateCardNumber) !== null && _d !== void 0 ? _d : true;
        _this.autoRemovePreviousCards = (_e = settings.autoRemovePreviousCards) !== null && _e !== void 0 ? _e : true;
        var shadowDirection = (_f = settings.shadowDirection) !== null && _f !== void 0 ? _f : 'bottom-right';
        var shadowDirectionSplit = shadowDirection.split('-');
        var xShadowShift = shadowDirectionSplit.includes('right') ? 1 : (shadowDirectionSplit.includes('left') ? -1 : 0);
        var yShadowShift = shadowDirectionSplit.includes('bottom') ? 1 : (shadowDirectionSplit.includes('top') ? -1 : 0);
        _this.element.style.setProperty('--xShadowShift', '' + xShadowShift);
        _this.element.style.setProperty('--yShadowShift', '' + yShadowShift);
        if (settings.topCard) {
            _this.addCard(settings.topCard);
        }
        else if (settings.cardNumber > 0) {
            _this.addCard(_this.getFakeCard());
        }
        if (settings.counter && ((_g = settings.counter.show) !== null && _g !== void 0 ? _g : true)) {
            if (settings.cardNumber === null || settings.cardNumber === undefined) {
                console.warn("Deck card counter created without a cardNumber");
            }
            _this.createCounter((_h = settings.counter.position) !== null && _h !== void 0 ? _h : 'bottom', (_j = settings.counter.extraClasses) !== null && _j !== void 0 ? _j : 'round', settings.counter.counterId);
            if ((_k = settings.counter) === null || _k === void 0 ? void 0 : _k.hideWhenEmpty) {
                _this.element.querySelector('.bga-cards_deck-counter').classList.add('hide-when-empty');
            }
        }
        _this.setCardNumber((_l = settings.cardNumber) !== null && _l !== void 0 ? _l : 0);
        return _this;
    }
    Deck.prototype.createCounter = function (counterPosition, extraClasses, counterId) {
        var left = counterPosition.includes('right') ? 100 : (counterPosition.includes('left') ? 0 : 50);
        var top = counterPosition.includes('bottom') ? 100 : (counterPosition.includes('top') ? 0 : 50);
        this.element.style.setProperty('--bga-cards-deck-left', "".concat(left, "%"));
        this.element.style.setProperty('--bga-cards-deck-top', "".concat(top, "%"));
        this.element.insertAdjacentHTML('beforeend', "\n            <div ".concat(counterId ? "id=\"".concat(counterId, "\"") : '', " class=\"bga-cards_deck-counter ").concat(extraClasses, "\"></div>\n        "));
    };
    /**
     * Get the the cards number.
     *
     * @returns the cards number
     */
    Deck.prototype.getCardNumber = function () {
        return this.cardNumber;
    };
    /**
     * Set the the cards number.
     *
     * @param cardNumber the cards number
     * @param topCard the deck top card. If unset, will generated a fake card (default). Set it to null to not generate a new topCard.
     */
    Deck.prototype.setCardNumber = function (cardNumber, topCard) {
        var _this = this;
        if (topCard === void 0) { topCard = undefined; }
        var promise = Promise.resolve(false);
        var oldTopCard = this.getTopCard();
        if (topCard !== null && cardNumber > 0) {
            var newTopCard = topCard || this.getFakeCard();
            if (!oldTopCard || this.manager.getId(newTopCard) != this.manager.getId(oldTopCard)) {
                promise = this.addCard(newTopCard, undefined, { autoUpdateCardNumber: false });
            }
        }
        else if (cardNumber == 0 && oldTopCard) {
            promise = this.removeCard(oldTopCard, { autoUpdateCardNumber: false });
        }
        this.cardNumber = cardNumber;
        this.element.dataset.empty = (this.cardNumber == 0).toString();
        var thickness = 0;
        this.thicknesses.forEach(function (threshold, index) {
            if (_this.cardNumber >= threshold) {
                thickness = index;
            }
        });
        this.element.style.setProperty('--thickness', "".concat(thickness, "px"));
        var counterDiv = this.element.querySelector('.bga-cards_deck-counter');
        if (counterDiv) {
            counterDiv.innerHTML = "".concat(cardNumber);
        }
        return promise;
    };
    Deck.prototype.addCard = function (card, animation, settings) {
        var _this = this;
        var _a, _b;
        if ((_a = settings === null || settings === void 0 ? void 0 : settings.autoUpdateCardNumber) !== null && _a !== void 0 ? _a : this.autoUpdateCardNumber) {
            this.setCardNumber(this.cardNumber + 1, null);
        }
        var promise = _super.prototype.addCard.call(this, card, animation, settings);
        if ((_b = settings === null || settings === void 0 ? void 0 : settings.autoRemovePreviousCards) !== null && _b !== void 0 ? _b : this.autoRemovePreviousCards) {
            promise.then(function () {
                var previousCards = _this.getCards().slice(0, -1); // remove last cards
                _this.removeCards(previousCards, { autoUpdateCardNumber: false });
            });
        }
        return promise;
    };
    Deck.prototype.cardRemoved = function (card, settings) {
        var _a;
        if ((_a = settings === null || settings === void 0 ? void 0 : settings.autoUpdateCardNumber) !== null && _a !== void 0 ? _a : this.autoUpdateCardNumber) {
            this.setCardNumber(this.cardNumber - 1);
        }
        _super.prototype.cardRemoved.call(this, card, settings);
    };
    Deck.prototype.removeAll = function (settings) {
        return __awaiter(this, void 0, void 0, function () {
            var promise;
            var _a, _b;
            return __generator(this, function (_c) {
                promise = _super.prototype.removeAll.call(this, __assign(__assign({}, settings), { autoUpdateCardNumber: (_a = settings === null || settings === void 0 ? void 0 : settings.autoUpdateCardNumber) !== null && _a !== void 0 ? _a : false }));
                if ((_b = settings === null || settings === void 0 ? void 0 : settings.autoUpdateCardNumber) !== null && _b !== void 0 ? _b : true) {
                    this.setCardNumber(0, null);
                }
                return [2 /*return*/, promise];
            });
        });
    };
    Deck.prototype.getTopCard = function () {
        var cards = this.getCards();
        return cards.length ? cards[cards.length - 1] : null;
    };
    /**
     * Shows a shuffle animation on the deck
     *
     * @param animatedCardsMax number of animated cards for shuffle animation.
     * @param fakeCardSetter a function to generate a fake card for animation. Required if the card id is not based on a numerci `id` field, or if you want to set custom card back
     * @returns promise when animation ends
     */
    Deck.prototype.shuffle = function (settings) {
        return __awaiter(this, void 0, void 0, function () {
            var animatedCardsMax, animatedCards, elements, getFakeCard, uid, i, newCard, newElement, pauseDelayAfterAnimation;
            var _this = this;
            var _a, _b, _c;
            return __generator(this, function (_d) {
                switch (_d.label) {
                    case 0:
                        animatedCardsMax = (_a = settings === null || settings === void 0 ? void 0 : settings.animatedCardsMax) !== null && _a !== void 0 ? _a : 10;
                        this.addCard((_b = settings === null || settings === void 0 ? void 0 : settings.newTopCard) !== null && _b !== void 0 ? _b : this.getFakeCard(), undefined, { autoUpdateCardNumber: false });
                        if (!this.manager.animationsActive()) {
                            return [2 /*return*/, Promise.resolve(false)]; // we don't execute as it's just visual temporary stuff
                        }
                        animatedCards = Math.min(10, animatedCardsMax, this.getCardNumber());
                        if (!(animatedCards > 1)) return [3 /*break*/, 4];
                        elements = [this.getCardElement(this.getTopCard())];
                        getFakeCard = function (uid) {
                            var newCard;
                            if (settings === null || settings === void 0 ? void 0 : settings.fakeCardSetter) {
                                newCard = {};
                                settings === null || settings === void 0 ? void 0 : settings.fakeCardSetter(newCard, uid);
                            }
                            else {
                                newCard = _this.fakeCardGenerator("".concat(_this.element.id, "-shuffle-").concat(uid));
                            }
                            return newCard;
                        };
                        uid = 0;
                        for (i = elements.length; i <= animatedCards; i++) {
                            newCard = void 0;
                            do {
                                newCard = getFakeCard(uid++);
                            } while (this.manager.getCardElement(newCard)); // To make sure there isn't a fake card remaining with the same uid
                            newElement = this.manager.createCardElement(newCard, false);
                            newElement.dataset.tempCardForShuffleAnimation = 'true';
                            this.element.prepend(newElement);
                            elements.push(newElement);
                        }
                        return [4 /*yield*/, this.manager.animationManager.playWithDelay(elements.map(function (element) { return new SlideAndBackAnimation(_this.manager, element, element.dataset.tempCardForShuffleAnimation == 'true'); }), 50)];
                    case 1:
                        _d.sent();
                        pauseDelayAfterAnimation = (_c = settings === null || settings === void 0 ? void 0 : settings.pauseDelayAfterAnimation) !== null && _c !== void 0 ? _c : 500;
                        if (!(pauseDelayAfterAnimation > 0)) return [3 /*break*/, 3];
                        return [4 /*yield*/, this.manager.animationManager.play(new BgaPauseAnimation({ duration: pauseDelayAfterAnimation }))];
                    case 2:
                        _d.sent();
                        _d.label = 3;
                    case 3: return [2 /*return*/, true];
                    case 4: return [2 /*return*/, Promise.resolve(false)];
                }
            });
        });
    };
    Deck.prototype.getFakeCard = function () {
        return this.fakeCardGenerator(this.element.id);
    };
    return Deck;
}(CardStock));
/**
 * A basic stock for a list of cards, based on flex.
 */
var LineStock = /** @class */ (function (_super) {
    __extends(LineStock, _super);
    /**
     * @param manager the card manager
     * @param element the stock element (should be an empty HTML Element)
     * @param settings a `LineStockSettings` object
     */
    function LineStock(manager, element, settings) {
        var _a, _b, _c, _d;
        var _this = _super.call(this, manager, element, settings) || this;
        _this.manager = manager;
        _this.element = element;
        element.classList.add('line-stock');
        element.dataset.center = ((_a = settings === null || settings === void 0 ? void 0 : settings.center) !== null && _a !== void 0 ? _a : true).toString();
        element.style.setProperty('--wrap', (_b = settings === null || settings === void 0 ? void 0 : settings.wrap) !== null && _b !== void 0 ? _b : 'wrap');
        element.style.setProperty('--direction', (_c = settings === null || settings === void 0 ? void 0 : settings.direction) !== null && _c !== void 0 ? _c : 'row');
        element.style.setProperty('--gap', (_d = settings === null || settings === void 0 ? void 0 : settings.gap) !== null && _d !== void 0 ? _d : '8px');
        return _this;
    }
    return LineStock;
}(CardStock));
/**
 * A stock with fixed slots (some can be empty)
 */
var SlotStock = /** @class */ (function (_super) {
    __extends(SlotStock, _super);
    /**
     * @param manager the card manager
     * @param element the stock element (should be an empty HTML Element)
     * @param settings a `SlotStockSettings` object
     */
    function SlotStock(manager, element, settings) {
        var _a, _b;
        var _this = _super.call(this, manager, element, settings) || this;
        _this.manager = manager;
        _this.element = element;
        _this.slotsIds = [];
        _this.slots = [];
        element.classList.add('slot-stock');
        _this.mapCardToSlot = settings.mapCardToSlot;
        _this.slotsIds = (_a = settings.slotsIds) !== null && _a !== void 0 ? _a : [];
        _this.slotClasses = (_b = settings.slotClasses) !== null && _b !== void 0 ? _b : [];
        _this.slotsIds.forEach(function (slotId) {
            _this.createSlot(slotId);
        });
        return _this;
    }
    SlotStock.prototype.createSlot = function (slotId) {
        var _a;
        this.slots[slotId] = document.createElement("div");
        this.slots[slotId].dataset.slotId = slotId;
        this.element.appendChild(this.slots[slotId]);
        (_a = this.slots[slotId].classList).add.apply(_a, __spreadArray(['slot'], this.slotClasses, true));
    };
    /**
     * Add a card to the stock.
     *
     * @param card the card to add
     * @param animation a `CardAnimation` object
     * @param settings a `AddCardToSlotSettings` object
     * @returns the promise when the animation is done (true if it was animated, false if it wasn't)
     */
    SlotStock.prototype.addCard = function (card, animation, settings) {
        var _a, _b;
        var slotId = (_a = settings === null || settings === void 0 ? void 0 : settings.slot) !== null && _a !== void 0 ? _a : (_b = this.mapCardToSlot) === null || _b === void 0 ? void 0 : _b.call(this, card);
        if (slotId === undefined) {
            throw new Error("Impossible to add card to slot : no SlotId. Add slotId to settings or set mapCardToSlot to SlotCard constructor.");
        }
        if (!this.slots[slotId]) {
            throw new Error("Impossible to add card to slot \"".concat(slotId, "\" : slot \"").concat(slotId, "\" doesn't exists."));
        }
        var newSettings = __assign(__assign({}, settings), { forceToElement: this.slots[slotId] });
        return _super.prototype.addCard.call(this, card, animation, newSettings);
    };
    /**
     * Change the slots ids. Will empty the stock before re-creating the slots.
     *
     * @param slotsIds the new slotsIds. Will replace the old ones.
     */
    SlotStock.prototype.setSlotsIds = function (slotsIds) {
        var _this = this;
        if (slotsIds.length == this.slotsIds.length && slotsIds.every(function (slotId, index) { return _this.slotsIds[index] === slotId; })) {
            // no change
            return;
        }
        this.removeAll();
        this.element.innerHTML = '';
        this.slotsIds = slotsIds !== null && slotsIds !== void 0 ? slotsIds : [];
        this.slotsIds.forEach(function (slotId) {
            _this.createSlot(slotId);
        });
    };
    /**
     * Add new slots ids. Will not change nor empty the existing ones.
     *
     * @param slotsIds the new slotsIds. Will be merged with the old ones.
     */
    SlotStock.prototype.addSlotsIds = function (newSlotsIds) {
        var _a;
        var _this = this;
        if (newSlotsIds.length == 0) {
            // no change
            return;
        }
        (_a = this.slotsIds).push.apply(_a, newSlotsIds);
        newSlotsIds.forEach(function (slotId) {
            _this.createSlot(slotId);
        });
    };
    SlotStock.prototype.canAddCard = function (card, settings) {
        var _a, _b;
        if (!this.contains(card)) {
            return true;
        }
        else {
            var closestSlot = this.getCardElement(card).closest('.slot');
            if (closestSlot) {
                var currentCardSlot = closestSlot.dataset.slotId;
                var slotId = (_a = settings === null || settings === void 0 ? void 0 : settings.slot) !== null && _a !== void 0 ? _a : (_b = this.mapCardToSlot) === null || _b === void 0 ? void 0 : _b.call(this, card);
                return currentCardSlot != slotId;
            }
            else {
                return true;
            }
        }
    };
    /**
     * Swap cards inside the slot stock.
     *
     * @param cards the cards to swap
     * @param settings for `updateInformations` and `selectable`
     */
    SlotStock.prototype.swapCards = function (cards, settings) {
        var _this = this;
        if (!this.mapCardToSlot) {
            throw new Error('You need to define SlotStock.mapCardToSlot to use SlotStock.swapCards');
        }
        var promises = [];
        var elements = cards.map(function (card) { return _this.manager.getCardElement(card); });
        var elementsRects = elements.map(function (element) { return element.getBoundingClientRect(); });
        var cssPositions = elements.map(function (element) { return element.style.position; });
        // we set to absolute so it doesn't mess with slide coordinates when 2 div are at the same place
        elements.forEach(function (element) { return element.style.position = 'absolute'; });
        cards.forEach(function (card, index) {
            var _a, _b;
            var cardElement = elements[index];
            var promise;
            var slotId = (_a = _this.mapCardToSlot) === null || _a === void 0 ? void 0 : _a.call(_this, card);
            _this.slots[slotId].appendChild(cardElement);
            cardElement.style.position = cssPositions[index];
            var cardIndex = _this.cards.findIndex(function (c) { return _this.manager.getId(c) == _this.manager.getId(card); });
            if (cardIndex !== -1) {
                _this.cards.splice(cardIndex, 1, card);
            }
            if ((_b = settings === null || settings === void 0 ? void 0 : settings.updateInformations) !== null && _b !== void 0 ? _b : true) { // after splice/push
                _this.manager.updateCardInformations(card);
            }
            _this.removeSelectionClassesFromElement(cardElement);
            promise = _this.animationFromElement(cardElement, elementsRects[index], {});
            if (!promise) {
                console.warn("CardStock.animationFromElement didn't return a Promise");
                promise = Promise.resolve(false);
            }
            promise.then(function () { var _a; return _this.setSelectableCard(card, (_a = settings === null || settings === void 0 ? void 0 : settings.selectable) !== null && _a !== void 0 ? _a : true); });
            promises.push(promise);
        });
        return Promise.all(promises);
    };
    return SlotStock;
}(LineStock));
var AllVisibleDeck = /** @class */ (function (_super) {
    __extends(AllVisibleDeck, _super);
    function AllVisibleDeck(manager, element, settings) {
        var _a, _b, _c, _d, _e, _f, _g, _h, _j;
        var _this = _super.call(this, manager, element, settings) || this;
        _this.manager = manager;
        _this.element = element;
        element.classList.add('all-visible-deck', (_a = settings.direction) !== null && _a !== void 0 ? _a : 'vertical');
        var cardWidth = _this.manager.getCardWidth();
        var cardHeight = _this.manager.getCardHeight();
        if (cardWidth && cardHeight) {
            _this.element.style.setProperty('--width', "".concat(cardWidth, "px"));
            _this.element.style.setProperty('--height', "".concat(cardHeight, "px"));
        }
        else {
            throw new Error("You need to set cardWidth and cardHeight in the card manager to use Deck.");
        }
        element.style.setProperty('--vertical-shift', (_c = (_b = settings.verticalShift) !== null && _b !== void 0 ? _b : settings.shift) !== null && _c !== void 0 ? _c : '3px');
        element.style.setProperty('--horizontal-shift', (_e = (_d = settings.horizontalShift) !== null && _d !== void 0 ? _d : settings.shift) !== null && _e !== void 0 ? _e : '3px');
        if (settings.counter && ((_f = settings.counter.show) !== null && _f !== void 0 ? _f : true)) {
            _this.createCounter((_g = settings.counter.position) !== null && _g !== void 0 ? _g : 'bottom', (_h = settings.counter.extraClasses) !== null && _h !== void 0 ? _h : 'round', settings.counter.counterId);
            if ((_j = settings.counter) === null || _j === void 0 ? void 0 : _j.hideWhenEmpty) {
                _this.element.querySelector('.bga-cards_deck-counter').classList.add('hide-when-empty');
                _this.element.dataset.empty = 'true';
            }
        }
        return _this;
    }
    AllVisibleDeck.prototype.addCard = function (card, animation, settings) {
        var promise;
        var order = this.cards.length;
        promise = _super.prototype.addCard.call(this, card, animation, settings);
        var cardId = this.manager.getId(card);
        var cardDiv = document.getElementById(cardId);
        cardDiv.style.setProperty('--order', '' + order);
        this.cardNumberUpdated();
        return promise;
    };
    /**
     * Set opened state. If true, all cards will be entirely visible.
     *
     * @param opened indicate if deck must be always opened. If false, will open only on hover/touch
     */
    AllVisibleDeck.prototype.setOpened = function (opened) {
        this.element.classList.toggle('opened', opened);
    };
    AllVisibleDeck.prototype.cardRemoved = function (card) {
        var _this = this;
        _super.prototype.cardRemoved.call(this, card);
        this.cards.forEach(function (c, index) {
            var cardId = _this.manager.getId(c);
            var cardDiv = document.getElementById(cardId);
            cardDiv.style.setProperty('--order', '' + index);
        });
        this.cardNumberUpdated();
    };
    AllVisibleDeck.prototype.createCounter = function (counterPosition, extraClasses, counterId) {
        var left = counterPosition.includes('right') ? 100 : (counterPosition.includes('left') ? 0 : 50);
        var top = counterPosition.includes('bottom') ? 100 : (counterPosition.includes('top') ? 0 : 50);
        this.element.style.setProperty('--bga-cards-deck-left', "".concat(left, "%"));
        this.element.style.setProperty('--bga-cards-deck-top', "".concat(top, "%"));
        this.element.insertAdjacentHTML('beforeend', "\n            <div ".concat(counterId ? "id=\"".concat(counterId, "\"") : '', " class=\"bga-cards_deck-counter ").concat(extraClasses, "\"></div>\n        "));
    };
    /**
     * Updates the cards number, if the counter is visible.
     */
    AllVisibleDeck.prototype.cardNumberUpdated = function () {
        var cardNumber = this.cards.length;
        this.element.style.setProperty('--tile-count', '' + cardNumber);
        this.element.dataset.empty = (cardNumber == 0).toString();
        var counterDiv = this.element.querySelector('.bga-cards_deck-counter');
        if (counterDiv) {
            counterDiv.innerHTML = "".concat(cardNumber);
        }
    };
    return AllVisibleDeck;
}(CardStock));
var CardManager = /** @class */ (function () {
    /**
     * @param game the BGA game class, usually it will be `this`
     * @param settings: a `CardManagerSettings` object
     */
    function CardManager(game, settings) {
        var _a;
        this.game = game;
        this.settings = settings;
        this.stocks = [];
        this.updateMainTimeoutId = [];
        this.updateFrontTimeoutId = [];
        this.updateBackTimeoutId = [];
        this.animationManager = (_a = settings.animationManager) !== null && _a !== void 0 ? _a : new AnimationManager(game);
    }
    /**
     * Returns if the animations are active. Animation aren't active when the window is not visible (`document.visibilityState === 'hidden'`), or `game.instantaneousMode` is true.
     *
     * @returns if the animations are active.
     */
    CardManager.prototype.animationsActive = function () {
        return this.animationManager.animationsActive();
    };
    CardManager.prototype.addStock = function (stock) {
        this.stocks.push(stock);
    };
    CardManager.prototype.removeStock = function (stock) {
        var index = this.stocks.indexOf(stock);
        if (index !== -1) {
            this.stocks.splice(index, 1);
        }
    };
    /**
     * @param card the card informations
     * @return the id for a card
     */
    CardManager.prototype.getId = function (card) {
        var _a, _b, _c;
        return (_c = (_b = (_a = this.settings).getId) === null || _b === void 0 ? void 0 : _b.call(_a, card)) !== null && _c !== void 0 ? _c : "card-".concat(card.id);
    };
    CardManager.prototype.createCardElement = function (card, visible) {
        var _a, _b, _c, _d, _e, _f;
        if (visible === void 0) { visible = true; }
        var id = this.getId(card);
        var side = visible ? 'front' : 'back';
        if (this.getCardElement(card)) {
            throw new Error('This card already exists ' + JSON.stringify(card));
        }
        var element = document.createElement("div");
        element.id = id;
        element.dataset.side = '' + side;
        element.innerHTML = "\n            <div class=\"card-sides\">\n                <div id=\"".concat(id, "-front\" class=\"card-side front\">\n                </div>\n                <div id=\"").concat(id, "-back\" class=\"card-side back\">\n                </div>\n            </div>\n        ");
        element.classList.add('card');
        document.body.appendChild(element);
        (_b = (_a = this.settings).setupDiv) === null || _b === void 0 ? void 0 : _b.call(_a, card, element);
        (_d = (_c = this.settings).setupFrontDiv) === null || _d === void 0 ? void 0 : _d.call(_c, card, element.getElementsByClassName('front')[0]);
        (_f = (_e = this.settings).setupBackDiv) === null || _f === void 0 ? void 0 : _f.call(_e, card, element.getElementsByClassName('back')[0]);
        document.body.removeChild(element);
        return element;
    };
    /**
     * @param card the card informations
     * @return the HTML element of an existing card
     */
    CardManager.prototype.getCardElement = function (card) {
        return document.getElementById(this.getId(card));
    };
    /**
     * Remove a card.
     *
     * @param card the card to remove
     * @param settings a `RemoveCardSettings` object
     */
    CardManager.prototype.removeCard = function (card, settings) {
        var _a;
        var id = this.getId(card);
        var div = document.getElementById(id);
        if (!div) {
            return Promise.resolve(false);
        }
        div.id = "deleted".concat(id);
        div.remove();
        // if the card is in a stock, notify the stock about removal
        (_a = this.getCardStock(card)) === null || _a === void 0 ? void 0 : _a.cardRemoved(card, settings);
        return Promise.resolve(true);
    };
    /**
     * Returns the stock containing the card.
     *
     * @param card the card informations
     * @return the stock containing the card
     */
    CardManager.prototype.getCardStock = function (card) {
        return this.stocks.find(function (stock) { return stock.contains(card); });
    };
    /**
     * Return if the card passed as parameter is suppose to be visible or not.
     * Use `isCardVisible` from settings if set, else will check if `card.type` is defined
     *
     * @param card the card informations
     * @return the visiblility of the card (true means front side should be displayed)
     */
    CardManager.prototype.isCardVisible = function (card) {
        var _a, _b, _c, _d;
        return (_c = (_b = (_a = this.settings).isCardVisible) === null || _b === void 0 ? void 0 : _b.call(_a, card)) !== null && _c !== void 0 ? _c : ((_d = card.type) !== null && _d !== void 0 ? _d : false);
    };
    /**
     * Set the card to its front (visible) or back (not visible) side.
     *
     * @param card the card informations
     * @param visible if the card is set to visible face. If unset, will use isCardVisible(card)
     * @param settings the flip params (to update the card in current stock)
     */
    CardManager.prototype.setCardVisible = function (card, visible, settings) {
        var _this = this;
        var _a, _b, _c, _d, _e, _f, _g, _h, _j, _k, _l, _m, _o;
        var element = this.getCardElement(card);
        if (!element) {
            return;
        }
        var isVisible = visible !== null && visible !== void 0 ? visible : this.isCardVisible(card);
        element.dataset.side = isVisible ? 'front' : 'back';
        var stringId = JSON.stringify(this.getId(card));
        if ((_a = settings === null || settings === void 0 ? void 0 : settings.updateMain) !== null && _a !== void 0 ? _a : false) {
            if (this.updateMainTimeoutId[stringId]) { // make sure there is not a delayed animation that will overwrite the last flip request
                clearTimeout(this.updateMainTimeoutId[stringId]);
                delete this.updateMainTimeoutId[stringId];
            }
            var updateMainDelay = (_b = settings === null || settings === void 0 ? void 0 : settings.updateMainDelay) !== null && _b !== void 0 ? _b : 0;
            if (isVisible && updateMainDelay > 0 && this.animationsActive()) {
                this.updateMainTimeoutId[stringId] = setTimeout(function () { var _a, _b; return (_b = (_a = _this.settings).setupDiv) === null || _b === void 0 ? void 0 : _b.call(_a, card, element); }, updateMainDelay);
            }
            else {
                (_d = (_c = this.settings).setupDiv) === null || _d === void 0 ? void 0 : _d.call(_c, card, element);
            }
        }
        if ((_e = settings === null || settings === void 0 ? void 0 : settings.updateFront) !== null && _e !== void 0 ? _e : true) {
            if (this.updateFrontTimeoutId[stringId]) { // make sure there is not a delayed animation that will overwrite the last flip request
                clearTimeout(this.updateFrontTimeoutId[stringId]);
                delete this.updateFrontTimeoutId[stringId];
            }
            var updateFrontDelay = (_f = settings === null || settings === void 0 ? void 0 : settings.updateFrontDelay) !== null && _f !== void 0 ? _f : 500;
            if (!isVisible && updateFrontDelay > 0 && this.animationsActive()) {
                this.updateFrontTimeoutId[stringId] = setTimeout(function () { var _a, _b; return (_b = (_a = _this.settings).setupFrontDiv) === null || _b === void 0 ? void 0 : _b.call(_a, card, element.getElementsByClassName('front')[0]); }, updateFrontDelay);
            }
            else {
                (_h = (_g = this.settings).setupFrontDiv) === null || _h === void 0 ? void 0 : _h.call(_g, card, element.getElementsByClassName('front')[0]);
            }
        }
        if ((_j = settings === null || settings === void 0 ? void 0 : settings.updateBack) !== null && _j !== void 0 ? _j : false) {
            if (this.updateBackTimeoutId[stringId]) { // make sure there is not a delayed animation that will overwrite the last flip request
                clearTimeout(this.updateBackTimeoutId[stringId]);
                delete this.updateBackTimeoutId[stringId];
            }
            var updateBackDelay = (_k = settings === null || settings === void 0 ? void 0 : settings.updateBackDelay) !== null && _k !== void 0 ? _k : 0;
            if (isVisible && updateBackDelay > 0 && this.animationsActive()) {
                this.updateBackTimeoutId[stringId] = setTimeout(function () { var _a, _b; return (_b = (_a = _this.settings).setupBackDiv) === null || _b === void 0 ? void 0 : _b.call(_a, card, element.getElementsByClassName('back')[0]); }, updateBackDelay);
            }
            else {
                (_m = (_l = this.settings).setupBackDiv) === null || _m === void 0 ? void 0 : _m.call(_l, card, element.getElementsByClassName('back')[0]);
            }
        }
        if ((_o = settings === null || settings === void 0 ? void 0 : settings.updateData) !== null && _o !== void 0 ? _o : true) {
            // card data has changed
            var stock = this.getCardStock(card);
            var cards = stock.getCards();
            var cardIndex = cards.findIndex(function (c) { return _this.getId(c) === _this.getId(card); });
            if (cardIndex !== -1) {
                stock.cards.splice(cardIndex, 1, card);
            }
        }
    };
    /**
     * Flips the card.
     *
     * @param card the card informations
     * @param settings the flip params (to update the card in current stock)
     */
    CardManager.prototype.flipCard = function (card, settings) {
        var element = this.getCardElement(card);
        var currentlyVisible = element.dataset.side === 'front';
        this.setCardVisible(card, !currentlyVisible, settings);
    };
    /**
     * Update the card informations. Used when a card with just an id (back shown) should be revealed, with all data needed to populate the front.
     *
     * @param card the card informations
     */
    CardManager.prototype.updateCardInformations = function (card, settings) {
        var newSettings = __assign(__assign({}, (settings !== null && settings !== void 0 ? settings : {})), { updateData: true });
        this.setCardVisible(card, undefined, newSettings);
    };
    /**
     * @returns the card with set in the settings (undefined if unset)
     */
    CardManager.prototype.getCardWidth = function () {
        var _a;
        return (_a = this.settings) === null || _a === void 0 ? void 0 : _a.cardWidth;
    };
    /**
     * @returns the card height set in the settings (undefined if unset)
     */
    CardManager.prototype.getCardHeight = function () {
        var _a;
        return (_a = this.settings) === null || _a === void 0 ? void 0 : _a.cardHeight;
    };
    /**
     * @returns the class to apply to selectable cards. Default 'bga-cards_selectable-card'.
     */
    CardManager.prototype.getSelectableCardClass = function () {
        var _a, _b;
        return ((_a = this.settings) === null || _a === void 0 ? void 0 : _a.selectableCardClass) === undefined ? 'bga-cards_selectable-card' : (_b = this.settings) === null || _b === void 0 ? void 0 : _b.selectableCardClass;
    };
    /**
     * @returns the class to apply to selectable cards. Default 'bga-cards_disabled-card'.
     */
    CardManager.prototype.getUnselectableCardClass = function () {
        var _a, _b;
        return ((_a = this.settings) === null || _a === void 0 ? void 0 : _a.unselectableCardClass) === undefined ? 'bga-cards_disabled-card' : (_b = this.settings) === null || _b === void 0 ? void 0 : _b.unselectableCardClass;
    };
    /**
     * @returns the class to apply to selected cards. Default 'bga-cards_selected-card'.
     */
    CardManager.prototype.getSelectedCardClass = function () {
        var _a, _b;
        return ((_a = this.settings) === null || _a === void 0 ? void 0 : _a.selectedCardClass) === undefined ? 'bga-cards_selected-card' : (_b = this.settings) === null || _b === void 0 ? void 0 : _b.selectedCardClass;
    };
    CardManager.prototype.getFakeCardGenerator = function () {
        var _this = this;
        var _a, _b;
        return (_b = (_a = this.settings) === null || _a === void 0 ? void 0 : _a.fakeCardGenerator) !== null && _b !== void 0 ? _b : (function (deckId) { return ({ id: _this.getId({ id: "".concat(deckId, "-fake-top-card") }) }); });
    };
    return CardManager;
}());
var CARD_WIDTH = 132;
var CARD_HEIGHT = 185;
var EVOLUTION_SIZE = 198;
var KEEP_CARDS_LIST = {
    base: [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23, 24, 25, 26, 27, 28, 29, 30, 31, 32, 33, 34, 35, 36, 37, 38, 39, 40, 41, 42, 43, 44, 45, 46, 47, 48],
    dark: [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 15, 16, 17, 18, 19, 21, 22, 23, 24, 25, 26, 29, 30, 31, 32, 33, 34, 36, 37, 38, 40, 41, 42, 43, 44, 45, 46, 47, 48, 49, 50, 51, 52, 53, 54, 55],
};
var DISCARD_CARDS_LIST = {
    base: [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18],
    dark: [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 12, 13, 15, 16, 17, 18, 19],
};
var COSTUME_CARDS_LIST = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12];
var TRANSFORMATION_CARDS_LIST = [1];
var FLIPPABLE_CARDS = [301];
var DARK_EDITION_CARDS_COLOR_MAPPINGS = {
    // keep
    1: {
        '724468': '6abd45',
        '6E3F63': 'a3ce51',
    },
    2: {
        '442E70': 'ea6284',
        '57347E': 'cc343f',
    },
    3: {
        '624A9E': 'f89b21',
        '624A9F': 'e86a24',
    },
    4: {
        '6FBA44': '25c1f2',
        '6FBA45': '9adbf2',
    },
    5: {
        '0068A1': 'e7622e',
        '0070AA': 'eec248',
    },
    6: {
        '5A6E79': '74a534',
    },
    7: {
        '5DB1DD': 'd89028',
    },
    8: {
        '7C7269': 'c24c47',
        '958B7F': 'e67765',
    },
    9: {
        '836380': 'c4432d',
        '836381': 'be6d4f',
    },
    10: {
        '42B4B4': 'ed2024',
        '25948B': 'b22127',
    },
    11: {
        '0C4E4A': '537dbf',
        '004C6E': 'abe0f7',
    },
    12: {
        '293066': 'f37671',
        '293067': 'ee2b2c',
    },
    13: {
        '060D29': 'ee323e',
        '0C1946': 'b92530',
    },
    14: {
        '060D29': 'ee323e',
        '0C1946': 'b92530',
    },
    15: {
        '823F24': 'eb5224',
        'FAAE5A': 'f09434',
    },
    16: {
        '5F6D7A': '5a56a5',
        '5F6D7B': '817ebb',
    },
    17: {
        '0481C4': 'e37ea0',
        '0481C5': 'c53240',
    },
    18: {
        '8E4522': '3262ae',
        '277C43': '70b3e3',
    },
    19: {
        '958877': 'f37c21',
    },
    21: {
        '2B63A5': 'e47825',
    },
    22: {
        'BBB595': 'fdb813',
        '835C25': 'e27926',
    },
    23: {
        '0C94D0': '6b489d',
        '0C94D1': 'af68aa',
    },
    24: {
        'AABEE1': 'fce150',
    },
    25: {
        '075087': '598c4e',
        '124884': '8ac667',
    },
    26: {
        '5E9541': '5c9942',
    },
    29: {
        '67374D': '2e73b9',
        '83B5B6': '5ebcea',
    },
    30: {
        '5B79A2': 'f16122',
    },
    31: {
        '0068A1': '306bb1',
    },
    32: {
        '462365': 'f59cb7',
        '563D5B': 'd46793',
    },
    33: {
        'CD599A': 'a43c8d',
        'E276A7': 'ed82b4',
    },
    34: {
        '1E345D': '6ea943',
        '1E345E': '447537',
    },
    36: {
        '2A7C3C': '537dbf',
        '6DB446': 'abe0f7',
    },
    37: {
        '8D6E5C': 'ee3343',
        'B16E44': 'ba2c38',
    },
    38: {
        '5C273B': 'ed6f2f',
    },
    40: {
        'A2B164': 'a3ce4e',
        'A07958': '437c3a',
    },
    41: {
        '5E7795': 'efcf43',
        '5E7796': 'e0a137',
    },
    42: {
        '142338': '2eb28b',
        '46617C': '91cc83',
    },
    43: {
        'A9C7AD': 'ee2d31',
        '4F6269': 'bb2026',
    },
    44: {
        'AE2B7B': 'ef549f',
    },
    45: {
        '56170E': 'f7941d',
        '56170F': 'fdbb43',
    },
    46: {
        'B795A5': '7cc145',
    },
    47: {
        '757A52': '23735f',
        '60664A': '23735f',
        '52593A': '23735f',
        '88A160': '1fa776',
    },
    48: {
        '443E56': 'bc4386',
    },
    // discard
    101: {
        'B180A0': 'b0782a',
        '9F7595': 'c5985d',
    },
    102: {
        '496787': 'f47920',
        '415C7A': 'faa61f',
    },
    103: {
        '993422': 'aa1f23',
        '5F6A70': 'e12d2b',
    },
    104: {
        '5BB3E2': '477b3a',
        '45A2D6': '89c546',
        'CE542B': '89c546',
    },
    105: {
        '5D657F': '358246',
    },
    106: {
        '7F2719': 'f7f39b',
        '812819': 'ffd530',
    },
    107: {
        '7F2719': 'f7f39b',
        '812819': 'ffd530',
    },
    108: {
        '71200F': 'ea7b24',
        '4E130B': 'faa61f',
    },
    109: {
        'B1624A': 'e63047',
    },
    110: {
        '645656': '6ea54a',
        '71625F': '3f612e',
    },
    112: {
        '5B79A2': 'eca729',
        '5B79A3': 'fdda50',
    },
    113: {
        'EE008E': 'cfad2e',
        '49236C': 'f8f16b',
    },
    115: {
        '684376': 'c8b62f',
        '41375F': 'f8f16b',
    },
    116: {
        '5F8183': 'f47920',
    },
    117: {
        'AF966B': '5269b1',
    },
    118: {
        '847443': '2e88b9',
        '8D7F4E': '63c0ed',
    },
};
var DARK_EDITION_CARDS_MAIN_COLOR = {
    // keep
    1: '#5ebb46',
    2: '#cc343f',
    3: '#e86a24',
    4: '#25c1f2',
    5: '#e7622e',
    6: '#74a534',
    7: '#d89028',
    8: '#c24c47',
    9: '#c4432d',
    10: '#ed2024',
    11: '#537dbf',
    12: '#ee2b2c',
    13: '#ee323e',
    14: '#ee323e',
    15: '#eb5224',
    16: '#5a56a5',
    17: '#c53240',
    18: '#3262ae',
    19: '#f37c21',
    21: '#e47825',
    22: '#e27926',
    23: '#6b489d',
    24: '#fce150',
    25: '#598c4e',
    26: '#5c9942',
    29: '#5ebcea',
    30: '#f16122',
    31: '#306bb1',
    32: '#d46793',
    33: '#a43c8d',
    36: '#537dbf',
    37: '#ee3343',
    38: '#ed6f2f',
    34: '#447537',
    40: '#437c3a',
    41: '#e0a137',
    42: '#2eb28b',
    43: '#ee2d31',
    44: '#ef549f',
    45: '#f9a229',
    46: '#7cc145',
    47: '#1fa776',
    48: '#bc4386',
    49: '#eeb91a',
    50: '#ee3934',
    51: '#f283ae',
    52: '#d65ca3',
    53: '#f15c37',
    54: '#4f7f3a',
    55: '#659640',
    // discard
    101: '#b0782a',
    102: '#f47920',
    103: '#e12d2b',
    104: '#5a802e',
    105: '#358246',
    106: '#ffd530',
    107: '#ffd530',
    108: '#d56529',
    109: '#e63047',
    110: '#6ea54a',
    112: '#eca729',
    113: '#cfad2e',
    115: '#c8b62f',
    116: '#f47920',
    117: '#5269b1',
    118: '#2e88b9',
    119: '#41813c',
};
var CardsManager = /** @class */ (function (_super) {
    __extends(CardsManager, _super);
    function CardsManager(game) {
        var _this = _super.call(this, game, {
            animationManager: game.animationManager,
            getId: function (card) { return "card-".concat(card.id); },
            setupDiv: function (card, div) {
                div.classList.add('kot-card');
                div.dataset.cardId = '' + card.id;
                div.dataset.cardType = '' + card.type;
            },
            setupFrontDiv: function (card, div) {
                _this.setFrontBackground(div, card.type, card.side);
                if (FLIPPABLE_CARDS.includes(card.type)) {
                    _this.setDivAsCard(div, 301, 0);
                }
                else if (card.type < 999) {
                    _this.setDivAsCard(div, card.type + (card.side || 0));
                }
                _this.game.addTooltipHtml(div.id, _this.getTooltip(card.type, card.side));
                if (card.tokens > 0) {
                    _this.placeTokensOnCard(card);
                }
            },
            setupBackDiv: function (card, div) {
                var darkEdition = _this.game.isDarkEdition();
                if (card.type >= 0 && card.type < 200) {
                    div.style.backgroundImage = "url('".concat(g_gamethemeurl, "img/").concat(darkEdition ? 'dark/' : '', "card-back.jpg')");
                }
                else if ((card.type >= 200 && card.type < 300) || card.type == -200) {
                    div.style.backgroundImage = "url('".concat(g_gamethemeurl, "img/card-back-costume.jpg')");
                }
                else if (FLIPPABLE_CARDS.includes(card.type)) {
                    _this.setFrontBackground(div, card.type, card.side);
                    _this.setDivAsCard(div, 301, 1);
                    _this.game.addTooltipHtml(div.id, _this.getTooltip(card.type, 1));
                }
                else if (card.type == 999) {
                    _this.setFrontBackground(div, card.type, card.side);
                }
            },
            isCardVisible: function (card) { return FLIPPABLE_CARDS.includes(card.type) ? card.side == 0 : card.type > 0; },
            cardWidth: 132,
            cardHeight: 185,
        }) || this;
        _this.game = game;
        _this.EVOLUTION_CARDS_TYPES = game.gamedatas.EVOLUTION_CARDS_TYPES;
        return _this;
    }
    CardsManager.prototype.getDistance = function (p1, p2) {
        return Math.sqrt(Math.pow((p1.x - p2.x), 2) + Math.pow((p1.y - p2.y), 2));
    };
    CardsManager.prototype.placeMimicOnCard = function (type, card, wickednessTiles) {
        var divId = this.getId(card);
        var div = document.getElementById(divId);
        if (type === 'tile') {
            var html = "<div id=\"".concat(divId, "-mimic-token-tile\" class=\"card-token mimic-tile stockitem\"></div>");
            dojo.place(html, divId);
            div.classList.add('wickedness-tile-stock');
            wickednessTiles.setDivAsCard(document.getElementById("".concat(divId, "-mimic-token-tile")), 106);
        }
        else {
            var div_1 = document.getElementById(divId);
            var cardPlaced = div_1.dataset.placed ? JSON.parse(div_1.dataset.placed) : { tokens: [] };
            cardPlaced.mimicToken = this.getPlaceOnCard(cardPlaced);
            var html = "<div id=\"".concat(divId, "-mimic-token\" style=\"left: ").concat(cardPlaced.mimicToken.x - 16, "px; top: ").concat(cardPlaced.mimicToken.y - 16, "px;\" class=\"card-token mimic token\"></div>");
            dojo.place(html, divId);
            div_1.dataset.placed = JSON.stringify(cardPlaced);
        }
    };
    CardsManager.prototype.removeMimicOnCard = function (type, card) {
        var divId = this.getId(card);
        var div = document.getElementById(divId);
        if (type === 'tile') {
            if (document.getElementById("".concat(divId, "-mimic-token-tile"))) {
                this.game.fadeOutAndDestroy("".concat(divId, "-mimic-token-tile"));
            }
            div.classList.remove('wickedness-tile-stock');
        }
        else {
            var cardPlaced = div.dataset.placed ? JSON.parse(div.dataset.placed) : { tokens: [] };
            cardPlaced.mimicToken = null;
            if (document.getElementById("".concat(divId, "-mimic-token"))) {
                this.game.fadeOutAndDestroy("".concat(divId, "-mimic-token"));
            }
            div.dataset.placed = JSON.stringify(cardPlaced);
        }
    };
    CardsManager.prototype.getPlaceOnCard = function (cardPlaced) {
        var _this = this;
        var newPlace = {
            x: Math.random() * 100 + 16,
            y: Math.random() * 100 + 16,
        };
        var protection = 0;
        var otherPlaces = cardPlaced.tokens.slice();
        if (cardPlaced.mimicToken) {
            otherPlaces.push(cardPlaced.mimicToken);
        }
        if (cardPlaced.superiorAlienTechnologyToken) {
            otherPlaces.push(cardPlaced.superiorAlienTechnologyToken);
        }
        while (protection < 1000 && otherPlaces.some(function (place) { return _this.getDistance(newPlace, place) < 32; })) {
            newPlace.x = Math.random() * 100 + 16;
            newPlace.y = Math.random() * 100 + 16;
            protection++;
        }
        return newPlace;
    };
    CardsManager.prototype.placeTokensOnCard = function (card, playerId) {
        var cardType = card.mimicType || card.type;
        if (![28, 41].includes(cardType)) {
            return;
        }
        var divId = this.getId(card);
        var div = document.getElementById(divId).getElementsByClassName('front')[0];
        if (!div) {
            return;
        }
        var cardPlaced = div.dataset.placed ? JSON.parse(div.dataset.placed) : { tokens: [] };
        var placed = cardPlaced.tokens;
        // remove tokens
        for (var i = card.tokens; i < placed.length; i++) {
            if (cardType === 28 && playerId) {
                this.game.slideToObjectAndDestroy("".concat(divId, "-token").concat(i), "energy-counter-".concat(playerId));
            }
            else {
                this.game.fadeOutAndDestroy("".concat(divId, "-token").concat(i));
            }
        }
        placed.splice(card.tokens, placed.length - card.tokens);
        // add tokens
        for (var i = placed.length; i < card.tokens; i++) {
            var newPlace = this.getPlaceOnCard(cardPlaced);
            placed.push(newPlace);
            var html = "<div id=\"".concat(divId, "-token").concat(i, "\" style=\"left: ").concat(newPlace.x - 16, "px; top: ").concat(newPlace.y - 16, "px;\" class=\"card-token ");
            if (cardType === 28) {
                html += "energy-cube cube-shape-".concat(Math.floor(Math.random() * 5));
            }
            else if (cardType === 41) {
                html += "smoke-cloud token";
            }
            html += "\"></div>";
            div.insertAdjacentHTML('beforeend', html);
        }
        div.dataset.placed = JSON.stringify(cardPlaced);
    };
    CardsManager.prototype.addCardsToStock = function (stock, cards, from) {
        var _this = this;
        if (!cards.length) {
            return;
        }
        cards.forEach(function (card) {
            stock.addToStockWithId(card.type, "".concat(card.id), from);
            var cardDiv = document.getElementById("".concat(stock.container_div.id, "_item_").concat(card.id));
            cardDiv.dataset.side = '' + card.side;
            if (card.side !== null) {
                _this.game.cardsManager.updateFlippableCardTooltip(cardDiv);
            }
        });
        cards.filter(function (card) { return card.tokens > 0; }).forEach(function (card) { return _this.placeTokensOnCard(card); });
    };
    CardsManager.prototype.moveToAnotherStock = function (sourceStock, destinationStock, card) {
        if (sourceStock === destinationStock) {
            return;
        }
        var sourceStockItemId = "".concat(sourceStock.container_div.id, "_item_").concat(card.id);
        if (document.getElementById(sourceStockItemId)) {
            this.addCardsToStock(destinationStock, [card], sourceStockItemId);
            //destinationStock.addToStockWithId(uniqueId, cardId, sourceStockItemId);
            sourceStock.removeFromStockById("".concat(card.id));
        }
        else {
            console.warn("".concat(sourceStockItemId, " not found in "), sourceStock);
            //destinationStock.addToStockWithId(uniqueId, cardId, sourceStock.container_div.id);
            this.addCardsToStock(destinationStock, [card], sourceStock.container_div.id);
        }
    };
    CardsManager.prototype.getCardNamePosition = function (cardTypeId, side) {
        if (side === void 0) { side = null; }
        switch (cardTypeId) {
            // KEEP
            case 3: return [0, 90];
            case 9: return [35, 95];
            case 11: return [0, 85];
            case 17: return [0, 85];
            case 19: return [0, 50];
            case 27: return [35, 65];
            case 38: return this.game.isOrigins() ? null : [0, 100];
            case 43: return [35, 100];
            case 45: return [0, 85];
            // TODODE
            // DISCARD
            case 102: return [30, 80];
            case 106:
            case 107: return [35, 65];
            case 111: return [35, 95];
            case 112: return [35, 35];
            case 113: return [35, 65];
            case 114: return [35, 95];
            case 115: return [0, 80];
            // COSTUME            
            case 209: return [15, 100];
            // TRANSFORMATION
            case 301: return {
                0: [10, 15],
                1: [10, 15],
            }[side];
        }
        return null;
    };
    CardsManager.prototype.getCardCost = function (cardTypeId) {
        switch (cardTypeId) {
            // KEEP
            case 1: return 6;
            case 2: return 3;
            case 3: return 5;
            case 4: return 4;
            case 5: return 4;
            case 6: return 5;
            case 7: return 3;
            case 8: return 3;
            case 9: return 3;
            case 10: return 4;
            case 11: return 3;
            case 12: return 4;
            case 13:
            case 14: return 7;
            case 15: return 4;
            case 16: return this.game.isDarkEdition() ? 6 : 5;
            case 17: return 3;
            case 18: return 5;
            case 19: return this.game.isDarkEdition() ? 6 : 4;
            case 20: return 4;
            case 21: return 5;
            case 22: return this.game.isDarkEdition() ? 5 : 3;
            case 23: return 7;
            case 24: return 5;
            case 25: return 2;
            case 26: return 3;
            case 27: return 8;
            case 28: return 3;
            case 29: return 7;
            case 30: return 4;
            case 31: return 3;
            case 32: return 4;
            case 33: return 3;
            case 34: return 3;
            case 35: return 4;
            case 36: return 3;
            case 37: return 3;
            case 38: return 4;
            case 39: return 3;
            case 40: return 6;
            case 41: return 4;
            case 42: return this.game.isDarkEdition() ? 3 : 2;
            case 43: return 5;
            case 44: return 3;
            case 45: return 4;
            case 46: return 4;
            case 47: return 3;
            case 48: return 6;
            case 49: return 4;
            case 50: return 3;
            case 51: return 2;
            case 52: return 6;
            case 53: return 4;
            case 54: return 3;
            case 55: return 4;
            case 56: return 4;
            case 57: return 5;
            case 58: return 5;
            case 59: return 5;
            case 60: return 4;
            case 61: return 4;
            case 62: return 3;
            case 63: return 9;
            case 64: return 3;
            case 65: return 4;
            case 66: return 3;
            // DISCARD
            case 101: return 5;
            case 102: return 4;
            case 103: return 3;
            case 104: return 5;
            case 105: return 8;
            case 106:
            case 107: return 7;
            case 108: return 3;
            case 109: return 7;
            case 110: return 6;
            case 111: return 3;
            case 112: return 4;
            case 113: return 5;
            case 114: return 3;
            case 115: return 6;
            case 116: return 6;
            case 117: return 4;
            case 118: return 6;
            case 119: return 0;
            case 120: return 5;
            case 121: return 4;
            case 122: return 7;
            // COSTUME
            case 201: return 4;
            case 202: return 4;
            case 203: return 3;
            case 204: return 4;
            case 205: return 3;
            case 206: return 4;
            case 207: return 5;
            case 208: return 4;
            case 209: return 3;
            case 210: return 4;
            case 211: return 4;
            case 212: return 3;
        }
        return null;
    };
    CardsManager.prototype.getColoredCardName = function (cardTypeId, side) {
        if (side === void 0) { side = null; }
        switch (cardTypeId) {
            // KEEP
            case 1: return _("[724468]Acid [6E3F63]Attack");
            case 2: return _("[442E70]Alien [57347E]Origin");
            case 3: return _("[624A9E]Alpha [624A9F]Monster");
            case 4: return _("[6FBA44]Armor [6FBA45]Plating");
            case 5: return _("[0068A1]Background [0070AA]Dweller");
            case 6: return _("[5A6E79]Burrowing");
            case 7: return _("[5DB1DD]Camouflage");
            case 8: return _("[7C7269]Complete [958B7F]Destruction");
            case 9: return _("[836380]Media-[836381]Friendly");
            case 10: return _("[42B4B4]Eater of [25948B]the Dead");
            case 11: return _("[0C4E4A]Energy [004C6E]Hoarder");
            case 12: return _("[293066]Even [293067]Bigger");
            case 13:
            case 14: return _("[060D29]Extra [0C1946]Head");
            case 15: return _("[823F24]Fire [FAAE5A]Breathing");
            case 16: return _("[5F6D7A]Freeze [5F6D7B]Time");
            case 17: return _("[0481C4]Friend of Children");
            case 18: return _("[8E4522]Giant [277C43]Brain");
            case 19: return _("[958877]Gourmet");
            case 20: return _("[7A673C]Healing [DC825F]Ray");
            case 21: return _("[2B63A5]Herbivore");
            case 22: return _("[BBB595]Herd [835C25]Culler");
            case 23: return _("[0C94D0]It Has a [0C94D1]Child!");
            case 24: return _("[AABEE1]Jets");
            case 25: return _("[075087]Made in [124884]a Lab");
            case 26: return _("[5E9541]Metamorph");
            case 27: return _("[85A8AA]Mimic");
            case 28: return _("[92534C]Battery [88524D]Monster");
            case 29: return _("[67374D]Nova [83B5B6]Breath");
            case 30: return _("[5B79A2]Detritivore");
            case 31: return _("[0068A1]Opportunist");
            case 32: return _("[462365]Parasitic [563D5B]Tentacles");
            case 33: return _("[CD599A]Plot [E276A7]Twist");
            case 34: return _("[1E345D]Poison [1E345E]Quills");
            case 35: return _("[3D5C33]Poison Spit");
            case 36: return _("[2A7C3C]Psychic [6DB446]Probe");
            case 37: return _("[8D6E5C]Rapid [B16E44]Healing");
            case 38: return _("[5C273B]Regeneration");
            case 39: return _("[007DC0]Rooting for the Underdog");
            case 40: return _("[A2B164]Shrink [A07958]Ray");
            case 41: return _("[5E7795]Smoke [5E7796]Cloud");
            case 42: return this.game.isDarkEdition() ? _("[2eb28b]Lunar [91cc83]Powered") : _("[142338]Solar [46617C]Powered");
            case 43: return _("[A9C7AD]Spiked [4F6269]Tail");
            case 44: return _("[AE2B7B]Stretchy");
            case 45: return _("[56170E]Energy [56170F]Drink");
            case 46: return _("[B795A5]Urbavore");
            case 47: return _("[757A52]We're [60664A]Only [52593A]Making It [88A160]Stronger!");
            case 48: return _("[443E56]Wings");
            case 49: return _("[eeb91a]Hibernation");
            case 50: return _("[ee3934]Nanobots");
            case 51: return _("[9e4163]Natural [f283ae]Selection");
            case 52: return _("[ad457e]Reflective [d65ca3]Hide");
            case 53: return _("[f2633b]Super [faa73b]Jump");
            case 54: return _("[4f7f3a]Unstable [a9d154]DNA");
            case 55: return _("[659640]Zombify");
            case 56: return _("[8ba121]Biofuel");
            case 57: return _("[b34c9c]Draining Ray");
            case 58: return _("[bed62f]Electric Armor");
            case 59: return _("[de6428]Flaming Aura");
            case 60: return _("[6db446]Gamma Blast");
            case 61: return _("[b34c9c]Hungry Urbavore");
            case 62: return _("[1f7e7f]Jagged Tactician");
            case 63: return _("[a65096]Orb of Doom");
            case 64: return _("[806f52]Scavenger");
            case 65: return _("[1c9c85]Shrinky");
            case 66: return _("[693a3a]Bull Headed");
            case 67: return /*TODOMB_*/ ("[xxx]Free [xxx]Will"); // TODOMB TODO: COLORs
            case 68: return /*TODOMB_*/ ("[xxx]Evasive [xxx]Mindbug");
            case 69: return /*TODOMB_*/ ("[xxx]No [xxx]Brain");
            // DISCARD
            case 101: return _("[B180A0]Apartment [9F7595]Building");
            case 102: return _("[496787]Commuter [415C7A]Train");
            case 103: return _("[993422]Corner [5F6A70]Store");
            case 104: return _("[5BB3E2]Death [45A2D6]From [CE542B]Above");
            case 105: return _("[5D657F]Energize");
            case 106:
            case 107: return _("[7F2719]Evacuation [812819]Orders");
            case 108: return _("[71200F]Flame [4E130B]Thrower");
            case 109: return _("[B1624A]Frenzy");
            case 110: return _("[645656]Gas [71625F]Refinery");
            case 111: return _("[815321]Heal");
            case 112: return _("[5B79A2]High Altitude [5B79A3]Bombing");
            case 113: return _("[EE008E]Jet [49236C]Fighters");
            case 114: return _("[68696B]National [53575A]Guard");
            case 115: return _("[684376]Nuclear [41375F]Power Plant");
            case 116: return _("[5F8183]Skyscraper");
            case 117: return _("[AF966B]Tank");
            case 118: return _("[847443]Vast [8D7F4E]Storm");
            case 119: return _("[83aa50]Monster [41813c]pets");
            case 120: return _("[775b43]Barricades");
            case 121: return _("[6b9957]Ice Cream Truck");
            case 122: return _("[f89c4c]Supertower");
            case 123: return /*TODOMB_*/ ("[xxx]Mindbug!"); // TODOMB TODO: COLORs
            case 124: return /*TODOMB_*/ ("[xxx]Dysfunctional [xxx]Mindbug");
            case 125: return /*TODOMB_*/ ("[xxx]Treasure");
            case 126: return /*TODOMB_*/ ("[xxx]Miraculous [xxx]Mindbug");
            // COSTUME
            case 201: return _("[353d4b]Astronaut");
            case 202: return _("[005c98]Ghost");
            case 203: return _("[213b75]Vampire");
            case 204: return _("[5a4f86]Witch");
            case 205: return _("[3c4b53]Devil");
            case 206: return _("[584b84]Pirate");
            case 207: return _("[bb6082]Princess");
            case 208: return _("[7e8670]Zombie");
            case 209: return _("[52373d]Cheerleader");
            case 210: return _("[146088]Robot");
            case 211: return _("[733010]Statue of liberty");
            case 212: return _("[2d4554]Clown");
            // TRANSFORMATION
            case 301: return {
                0: _("[deaa26]Biped [72451c]Form"),
                1: _("[982620]Beast [de6526]Form"),
                null: _("[982620]Beast [de6526]Form"),
            }[side];
            // CONSUMABLE
            case 401: return /*TODOMB_*/ ("[xxx]Overequipped [xxx]Trapper"); // TODOMB TODO: COLORs
            case 402: return /*TODOMB_*/ ("[xxx]Legendary [xxx]Hunter");
            case 403: return /*TODOMB_*/ ("[xxx]Unreliable [xxx]Targeting");
            case 404: return /*TODOMB_*/ ("[xxx]Sneaky [xxx]Alloy");
            case 405: return /*TODOMB_*/ ("[xxx]Offensive [xxx]Protocol");
            case 406: return /*TODOMB_*/ ("[xxx]Arcane [xxx]Scepter");
            case 407: return /*TODOMB_*/ ("[xxx]Energy [xxx]Armor");
            case 408: return /*TODOMB_*/ ("[xxx]Strange [xxx]Design");
            case 409: return /*TODOMB_*/ ("[xxx]Ancestral [xxx]Defense");
            case 410: return /*TODOMB_*/ ("[xxx]Toxic [xxx]Petals");
            case 411: return /*TODOMB_*/ ("[xxx]Explosive [xxx]Crystals");
            case 412: return /*TODOMB_*/ ("[xxx]Electro-[xxx]Whip");
            case 413: return /*TODOMB_*/ ("[xxx]Bold [xxx]Maneuver");
            case 414: return /*TODOMB_*/ ("[xxx]Unfair [xxx]Gift");
            case 415: return /*TODOMB_*/ ("[xxx]Maximum [xxx]Effort");
            case 416: return /*TODOMB_*/ ("[xxx]Deadly [xxx]Shell");
            case 417: return /*TODOMB_*/ ("[xxx]Spatial [xxx]Hunter");
        }
        return null;
    };
    CardsManager.prototype.getCardName = function (cardTypeId, state, side) {
        if (side === void 0) { side = null; }
        var coloredCardName = this.getColoredCardName(cardTypeId, side);
        if (state == 'text-only') {
            return coloredCardName === null || coloredCardName === void 0 ? void 0 : coloredCardName.replace(/\[(\w+)\]/g, '');
        }
        else if (state == 'span') {
            var first_1 = true;
            var colorMapping_1 = this.game.isDarkEdition() ? DARK_EDITION_CARDS_COLOR_MAPPINGS[cardTypeId] : null;
            return (coloredCardName === null || coloredCardName === void 0 ? void 0 : coloredCardName.replace(/\[(\w+)\]/g, function (index, color) {
                var mappedColor = color;
                if (colorMapping_1 === null || colorMapping_1 === void 0 ? void 0 : colorMapping_1[color]) {
                    mappedColor = colorMapping_1[color];
                }
                var span = "<span style=\"-webkit-text-stroke-color: #".concat(mappedColor, ";\">");
                if (first_1) {
                    first_1 = false;
                }
                else {
                    span = "</span>" + span;
                }
                return span;
            })) + "".concat(first_1 ? '' : '</span>');
        }
        return null;
    };
    CardsManager.prototype.getCardDescription = function (cardTypeId, side) {
        if (side === void 0) { side = null; }
        switch (cardTypeId) {
            // KEEP
            case 1: return _("<strong>Add</strong> [diceSmash] to your Roll");
            case 2: return _("<strong>Buying cards costs you 1 less [Energy].</strong>");
            case 3: return _("<strong>Gain 1[Star]</strong> when you roll at least [dieClaw].");
            case 4: return _("<strong>Do not lose [heart] when you lose exactly 1[heart].</strong>");
            case 5: return _("<strong>You can always reroll any [dice3]</strong> you have.");
            case 6: return _("<strong>Add [diceSmash] to your Roll while you are in Tokyo. When you Yield Tokyo, the monster taking it loses 1[heart].</strong>");
            case 7: return _("If you lose [heart], roll a die for each [heart] you lost. <strong>Each [diceHeart] reduces the loss by 1[heart].</strong>");
            case 8: return _("If you roll [dice1][dice2][dice3][diceHeart][diceSmash][diceEnergy] <strong>gain 9[Star]</strong> in addition to the regular effects.");
            case 9: return _("<strong>Gain 1[Star]</strong> whenever you buy a Power card.");
            case 10: return _("<strong>Gain 3[Star]</strong> every time a Monster's [Heart] goes to [Skull].");
            case 11: return _("<strong>You gain 1[Star]</strong> for every 6[Energy] you have at the end of your turn.");
            case 12: return _("<strong>+2[Heart] when you buy this card.</strong> Your maximum [Heart] is increased to 12[Heart] as long as you own this card.");
            case 13:
            case 14: return _("<strong>You get 1 extra die.</strong>");
            case 15: return _("<strong>when you roll at least [dieClaw]</strong>, your neighbor(s) at the table lose 1 extra [heart].");
            case 16: return _("On a turn where you roll at least [die1][die1][die1] or more, <strong>you can take another turn</strong> with one less die.");
            case 17: return _("When you gain any [Energy] <strong>gain 1 extra [Energy].</strong>");
            case 18: return _("<strong>You have 1 extra die Roll</strong> each turn.");
            case 19: return _("When you roll at least [die1][die1][die1] <strong>gain 2 extra [Star]</strong> in addition to the regular effects.");
            case 20: return _("<strong>You can use your [diceHeart] to make other Monsters gain [Heart].</strong> Each Monster must pay you 2[Energy] (or 1[Energy] if it's their last one) for each [Heart] they gain this way");
            case 21: return _("<strong>Gain 1[Star]</strong> at the end of your turn if you don't make anyone lose [Heart].");
            case 22: return _("You can <strong>change one of your dice to a [dice1]</strong> each turn.");
            case 23: return this.game.isDarkEdition() ?
                _("If you reach [Skull], discard all your cards and tiles, remove your Counter from the Wickedness Gauge, lose all your [Star] and Yield Tokyo. <strong>Gain 10[Heart] and continue playing.</strong>") :
                _("If you reach [Skull], discard all your cards and lose all your [Star]. <strong>Gain 10[Heart] and continue playing outside Tokyo.</strong>");
            case 24: return _("<strong>You don't lose [Heart]<strong> if you decide to Yield Tokyo.");
            case 25: return _("During the Buy Power cards step, you can <strong>peek at the top card of the deck and buy it</strong> or put it back on top of the deck.");
            case 26: return _("At the end of your turn you can <strong>discard any [keep] cards you have to gain their full cost in [Energy].</strong>");
            case 27: return _("<strong>Choose a [keep] card any monster has in play</strong> and put a Mimic token on it. <strong>This card counts as a duplicate of that card as if you had just bought it.</strong> Spend 1[Energy] at the start of your turn to move the Mimic token and change the card you are mimicking.");
            case 28: return _("When you buy <i>${card_name}</i>, put 6[Energy] on it from the bank. At the start of your turn <strong>take 2[Energy] off and add them to your pool.</strong> When there are no [Energy] left discard this card.").replace('${card_name}', this.getCardName(cardTypeId, 'text-only'));
            case 29: return _("<strong>All of your [dieClaw] Smash all other Monsters.</strong>");
            case 30: return _("<strong>When you roll at least [die1][die2][die3], gain 2[Star],</strong> in addition to the regular effects.");
            case 31: return _("<strong>Whenever a Power card is revealed you have the option of buying it</strong> immediately.");
            case 32: return _("<strong>You can buy Power cards from other monsters.</strong> Pay them the [Energy] cost.");
            case 33: return _("Before resolving your dice, you may <strong>change one die to any result</strong>. Discard when used.");
            case 34: return _("When you roll at least [dice2][dice2][dice2] or more, <strong>add [dieClaw][dieClaw] to your Roll</strong>.");
            case 35: return _("Give one <i>Poison</i> token to each Monster you Smash with your [diceSmash]. <strong>At the end of their turn, Monsters lose 1[Heart] for each <i>Poison</i> token they have on them.</strong> A <i>Poison</i> token can be discarded by using a [diceHeart] instead of gaining 1[Heart].");
            case 36: return _("<strong>You can reroll a die of your choice after the last Roll of each other Monster.</strong> If the result of your reroll is [dieHeart], discard this card.");
            case 37: return _("Spend 2[Energy] at any time to <strong>gain 1[Heart].</strong> This may be used to prevent your health from being reduced to [Skull].");
            case 38: return _("When you gain [Heart], you <strong>gain 1 extra [Heart].</strong>");
            case 39: return _("At the end of your turn, if you have the fewest [Star], <strong>gain 1 [Star].</strong>");
            case 40: return _("Give 1 <i>Shrink Ray</i> to each Monster you Smash with your [diceSmash]. <strong>At the beginning of their turn, Monster roll 1 less dice for each <i>Shrink Ray</i> token they have on them</strong>. A <i>Shrink Ray</i> token can be discarded by using a [diceHeart] instead of gaining 1[Heart].");
            case 41: return _("Place 3 <i>Smoke</i> counters on this card. <strong>Spend 1 <i>Smoke</i> counter for an extra Roll.</strong> Discard this card when all <i>Smoke</i> counters are spent.");
            case 42: return _("At the end of your turn <strong>gain 1[Energy] if you have no [Energy].</strong>");
            case 43: return _("<strong>If you roll at least one [diceSmash], add [diceSmash]</strong> to your Roll.");
            case 44: return _("Before resolving your dice, you can spend 2[Energy] to <strong>change one of your dice to any result.</strong>");
            case 45: return _("Spend 1[Energy] to <strong>get 1 extra die Roll.</strong>");
            case 46: return _("<strong>Gain 1 extra [Star]</strong> when beginning your turn in Tokyo. If you are in Tokyo and you roll at least one [diceSmash], <strong>add [diceSmash] to your Roll.</strong>");
            case 47: return _("When you lose at least 2[Heart] you <strong>gain 1[Energy].</strong>");
            case 48: return _("<strong>Spend 2[Energy] to not lose [Heart]<strong> this turn.");
            case 49: return "<div><i>".concat(_("You CANNOT buy this card while in TOKYO"), "</i></div>") + _("<strong>You no longer take damage.</strong> You cannot move, even if Tokyo is empty. You can no longer buy cards. <strong>The only results you can use are [diceHeart] and [diceEnergy].</strong> Discard this card to end its effects and restrictions immediately.");
            case 50: return _("At the start of your turn, if you have fewer than 3[Heart], <strong>gain 2[Heart].</strong>");
            case 51: return '<div><strong>+4[Energy] +4[Heart]</strong></div>' + _("<strong>Use an extra die.</strong> If you ever end one of your turns with at least [dice3], you lose all your [Heart].");
            case 52: return _("<strong>Any Monster who makes you lose [Heart] loses 1[Heart]</strong> as well.");
            case 53: return _("Once each player’s turn, you may spend 1[Energy] <strong>to negate the loss of 1[Heart].</strong>");
            case 54: return _("When you Yield Tokyo, <strong>you may exchange this card</strong> with a card of your choice from the Monster who Smashed you.");
            case 55: return _("If you reach [Skull] for the first time in this game, <strong>discard all your cards and tiles, remove your Counter from the Wickedness Gauge, lose all your [Star], Yield Tokyo, gain 12[Heart] and continue playing.</strong> For the rest of the game, your maximum [Heart] is increased to 12[Heart] and <strong>you can’t use [diceHeart] anymore.</strong>");
            case 56: return _("You may use [dieHeart] as [dieEnergy].");
            case 57: return _("When you roll at least 4 of a kind, <strong>steal 1[Star] from the Monster(s) with the most [Star].</strong>");
            case 58: return _("When you lose any [Heart], you may spend 1[Energy] to <strong>reduce the loss of [Heart] by 1.</strong>");
            case 59: return _("When you roll at least 4 of a kind, <strong>all other Monsters lose 1[Heart].</strong>");
            case 60: return _("When you take control of Tokyo, <strong>all other Monsters lose 1[Heart].</strong>");
            case 61: return _("<strong>Gain 1[Star]</strong> when you take control of Tokyo.");
            case 62: return _("When you Yield Tokyo, <strong>the Monster taking it loses 1[Heart]</strong> and you <strong>gain 1[Energy].</strong>");
            case 63: return _("<strong>Other Monsters lose 1[Heart]</strong> each time they reroll.");
            case 64: return _("<strong>You may buy cards from the discard pile.</strong> [Discard] cards bought this way are put on the bottom of the deck.");
            case 65: return _("<strong>You may use [die2] as [die1].");
            case 66: return _("<strong>Gain 1[Star]</strong> when you are able to Yield Tokyo but choose not to.");
            // DISCARD
            case 101: return "<strong>+ 3[Star].</strong>";
            case 102: return "<strong>+ 2[Star].</strong>";
            case 103: return "<strong>+ 1[Star].</strong>";
            case 104: return _("<strong>+ 2[Star] and take control of Tokyo</strong> if you don't already control it. All other Monsters must Yield Tokyo.");
            case 105: return "<strong>+ 9[Energy].</strong>";
            case 106:
            case 107: return _("<strong>All other Monsters lose 5[Star].</strong>");
            case 108: return _("<strong>All other Monsters lose 2[Heart].</strong>");
            case 109: return _("<strong>Take another turn</strong> after this one");
            case 110: return _("<strong>+ 2[Star] and all other monsters lose 3[Heart].</strong>");
            case 111: return "<strong>+ 2[Heart]</strong>";
            case 112: return _("<strong>All Monsters</strong> (including you) <strong>lose 3[Heart].</strong>");
            case 113: return "<strong>+5[Star] -4[Heart].</strong>";
            case 114: return "<strong>+2[Star] -2[Heart].</strong>";
            case 115: return "<strong>+2[Star] +3[Heart].</strong>";
            case 116: return "<strong>+4[Star].";
            case 117: return "<strong>+4[Star] -3[Heart].</strong>";
            case 118: return _("<strong>+ 2[Star] and all other Monsters lose 1[Energy] for every 2[Energy]</strong> they have.");
            case 119: return _("<strong>All Monsters</strong> (including you) <strong>lose 3[Star].</strong>");
            case 120: return _("<strong>All other Monsters lose 3[Star].</strong>");
            case 121: return "<strong>+1[Star] +2[Heart].</strong>";
            case 122: return "<strong>+5[Star].";
            // COSTUME
            case 201: return _("<strong>If you reach 17[Star],</strong> you win the game");
            case 202: return _("At the end of each Monster's turn, if you lost at least 1[Heart] <strong>that turn, gain 1[Heart].</strong>");
            case 203: return _("At the end of each Monster's turn, if you made another Monster lose at least 1[Heart], <strong>gain 1[Heart].</strong>");
            case 204: return _("If you must be wounded <strong>by another Monster,</strong> you can reroll one of their dice.");
            case 205: return _("On your turn, when you make other Monsters lose at least 1[Heart], <strong>they lose an extra [Heart].</strong>");
            case 206: return _("<strong>Steal 1[Energy]</strong> from each Monster you made lose at least 1[Heart].");
            case 207: return _("<strong>Gain 1[Star] at the start of your turn.</strong>");
            case 208: return _("You are not eliminated if you reach 0[Heart]. <strong>You cannot lose [Heart]</strong> as long as you have 0[Heart]. If you lose this card while you have 0[Heart], you are immediately eliminated.");
            case 209: return _("<strong>You can choose to cheer for another Monster on their turn.</strong> If you do, add [diceSmash] to their roll.");
            case 210: return _("You can choose to lose [Energy] instead of [Heart].");
            case 211: return _("You have an <strong>extra Roll.</strong>");
            case 212: return _("If you roll [dice1][dice2][dice3][diceHeart][diceSmash][diceEnergy], you can <strong>change the result for every die.</strong>");
            // TRANSFORMATION 
            case 301: return {
                0: _("Before the Buy Power cards phase, you may spend 1[Energy] to flip this card."),
                1: _("During the Roll Dice phase, you may reroll one of your dice an extra time. You cannot buy any more Power cards. <em>Before the Buy Power cards phase, you may spend 1[Energy] to flip this card.</em>"),
            }[side];
        }
        return null;
    };
    CardsManager.prototype.updateFlippableCardTooltip = function (cardDiv) {
        var type = Number(cardDiv.dataset.type);
        if (!FLIPPABLE_CARDS.includes(type)) {
            return;
        }
        this.game.addTooltipHtml(cardDiv.id, this.getTooltip(type, Number(cardDiv.dataset.side)));
    };
    CardsManager.prototype.getTooltip = function (cardTypeId, side) {
        if (side === void 0) { side = null; }
        if (cardTypeId === 999) {
            return _("The Golden Scarab affects certain Curse cards. At the start of the game, the player who will play last gets the Golden Scarab.");
        }
        var cost = this.getCardCost(cardTypeId);
        var tooltip = "<div class=\"card-tooltip\">\n            <p><strong>".concat(this.getCardName(cardTypeId, 'text-only', side), "</strong></p>");
        if (cost !== null) {
            tooltip += "<p class=\"cost\">".concat(dojo.string.substitute(_("Cost : ${cost}"), { 'cost': cost }), " <span class=\"icon energy\"></span></p>");
        }
        tooltip += "<p>".concat(formatTextIcons(this.getCardDescription(cardTypeId, side)), "</p>");
        if (FLIPPABLE_CARDS.includes(cardTypeId) && side !== null) {
            var otherSide = side == 1 ? 0 : 1;
            var tempDiv = document.createElement('div');
            tempDiv.classList.add('stockitem');
            tempDiv.style.width = "".concat(CARD_WIDTH, "px");
            tempDiv.style.height = "".concat(CARD_HEIGHT, "px");
            tempDiv.style.position = "relative";
            tempDiv.style.backgroundImage = "url('".concat(g_gamethemeurl, "img/").concat(this.getImageName(cardTypeId), "-cards.jpg')");
            tempDiv.style.backgroundPosition = "-".concat(otherSide * 100, "% 0%");
            document.body.appendChild(tempDiv);
            this.setDivAsCard(tempDiv, cardTypeId, otherSide);
            document.body.removeChild(tempDiv);
            tooltip += "<p>".concat(_("Other side :"), "<br>").concat(tempDiv.outerHTML, "</p>");
        }
        tooltip += "</div>";
        return tooltip;
    };
    CardsManager.prototype.getCardTypeName = function (cardType) {
        if (cardType < 100) {
            return _('Keep');
        }
        else if (cardType < 200) {
            return _('Discard');
        }
        else if (cardType < 300) {
            return _('Costume');
        }
        else if (cardType < 400) {
            return _('Transformation');
        }
    };
    CardsManager.prototype.getCardTypeClass = function (cardType) {
        if (cardType < 100) {
            return 'keep';
        }
        else if (cardType < 200) {
            return 'discard';
        }
        else if (cardType < 300) {
            return 'costume';
        }
        else if (cardType < 400) {
            return 'transformation';
        }
    };
    CardsManager.prototype.setDivAsCard = function (cardDiv, cardType, side) {
        if (side === void 0) { side = null; }
        cardDiv.classList.add('kot-card');
        cardDiv.dataset.design = cardType < 200 && this.game.isDarkEdition() ? 'dark-edition' : 'standard';
        var type = this.getCardTypeName(cardType);
        var description = formatTextIcons(this.getCardDescription(cardType, side));
        var position = this.getCardNamePosition(cardType, side);
        cardDiv.innerHTML = "<div class=\"bottom\"></div>\n        <div class=\"name-wrapper\" ".concat(position ? "style=\"left: ".concat(position[0], "px; top: ").concat(position[1], "px;\"") : '', ">\n            <div class=\"outline\">").concat(this.getCardName(cardType, 'span', side), "</div>\n            <div class=\"text\">").concat(this.getCardName(cardType, 'text-only', side), "</div>\n        </div>\n        <div class=\"type-wrapper ").concat(this.getCardTypeClass(cardType), "\">\n            <div class=\"outline\">").concat(type, "</div>\n            <div class=\"text\">").concat(type, "</div>\n        </div>\n        \n        <div class=\"description-wrapper\">").concat(description, "</div>");
        if (this.game.isDarkEdition() && DARK_EDITION_CARDS_MAIN_COLOR[cardType]) {
            cardDiv.style.setProperty('--main-color', DARK_EDITION_CARDS_MAIN_COLOR[cardType]);
        }
        var textHeight = cardDiv.getElementsByClassName('description-wrapper')[0].clientHeight;
        if (textHeight > 80) {
            cardDiv.getElementsByClassName('description-wrapper')[0].style.fontSize = '6pt';
            textHeight = cardDiv.getElementsByClassName('description-wrapper')[0].clientHeight;
        }
        var height = Math.min(textHeight, 116);
        cardDiv.getElementsByClassName('bottom')[0].style.top = "".concat(166 - height, "px");
        cardDiv.getElementsByClassName('type-wrapper')[0].style.top = "".concat(168 - height, "px");
        var nameTopPosition = (position === null || position === void 0 ? void 0 : position[1]) || 14;
        var nameWrapperDiv = cardDiv.getElementsByClassName('name-wrapper')[0];
        var nameDiv = nameWrapperDiv.getElementsByClassName('text')[0];
        var spaceBetweenDescriptionAndName = (155 - height) - (nameTopPosition + nameDiv.clientHeight);
        if (spaceBetweenDescriptionAndName < 0) {
            nameWrapperDiv.style.top = "".concat(Math.max(5, nameTopPosition + spaceBetweenDescriptionAndName), "px");
        }
    };
    CardsManager.prototype.setFrontBackground = function (cardDiv, cardType, side) {
        if (side === void 0) { side = null; }
        var darkEdition = this.game.isDarkEdition();
        var version = darkEdition ? 'dark' : 'base';
        if (cardType < 100) {
            var originsCard = cardType >= 56;
            var keepcardsurl = originsCard ?
                "".concat(g_gamethemeurl, "img/cards/cards-keep-origins.jpg") :
                "".concat(g_gamethemeurl, "img/").concat(darkEdition ? 'dark/' : '', "keep-cards.jpg");
            cardDiv.style.backgroundImage = "url('".concat(keepcardsurl, "')");
            var index = originsCard ?
                cardType - 56 :
                KEEP_CARDS_LIST[version].findIndex(function (type) { return type == cardType; });
            cardDiv.style.backgroundPositionX = "".concat((index % 10) * 100 / 9, "%");
            cardDiv.style.backgroundPositionY = "".concat(Math.floor(index / 10) * 100 / (originsCard ? 1 : 4), "%");
            if (cardType == 38 && this.game.isOrigins()) {
                cardDiv.style.backgroundImage = "url('".concat(g_gamethemeurl, "img/cards/cards-regeneration-origins.jpg')");
                cardDiv.style.backgroundPosition = "0% 0%";
            }
        }
        else if (cardType < 200) {
            var originsCard = cardType >= 120;
            var discardcardsurl = originsCard ?
                "".concat(g_gamethemeurl, "img/cards/cards-discard-origins.jpg") :
                "".concat(g_gamethemeurl, "img/").concat(darkEdition ? 'dark/' : '', "discard-cards.jpg");
            var index = originsCard ?
                cardType - 120 :
                DISCARD_CARDS_LIST[version].findIndex(function (type) { return type == cardType % 100; });
            cardDiv.style.backgroundImage = "url('".concat(discardcardsurl, "')");
            cardDiv.style.backgroundPositionX = "".concat((index % 10) * 100 / 9, "%");
            cardDiv.style.backgroundPositionY = "".concat(Math.floor(index / 10) * 100, "%");
        }
        else if (cardType < 300) {
            var index = COSTUME_CARDS_LIST.findIndex(function (type) { return type == cardType % 100; });
            var costumecardsurl = "".concat(g_gamethemeurl, "img/costume-cards.jpg");
            cardDiv.style.backgroundImage = "url('".concat(costumecardsurl, "')");
            cardDiv.style.backgroundPositionX = "".concat((index % 10) * 100 / 9, "%");
            cardDiv.style.backgroundPositionY = "".concat(Math.floor(index / 10) * 100, "%");
        }
        else if (cardType < 400) {
            var transformationcardsurl = "".concat(g_gamethemeurl, "img/transformation-cards.jpg");
            cardDiv.style.backgroundImage = "url('".concat(transformationcardsurl, "')");
            cardDiv.style.backgroundPositionX = "".concat(side * 100, "%");
            cardDiv.style.backgroundPositionY = '0%';
        }
        else if (cardType == 999) {
            var anubiscardsurl = "".concat(g_gamethemeurl, "img/anubis-cards.jpg");
            cardDiv.style.backgroundImage = "url(".concat(anubiscardsurl);
            cardDiv.style.backgroundPositionX = '0%';
            cardDiv.style.backgroundPositionY = '0%';
        }
    };
    CardsManager.prototype.getImageName = function (cardType) {
        if (cardType < 100) {
            return 'keep';
        }
        else if (cardType < 200) {
            return 'discard';
        }
        else if (cardType < 300) {
            return 'costume';
        }
        else if (cardType < 400) {
            return 'transformation';
        }
    };
    CardsManager.prototype.generateCardDiv = function (card) {
        var tempDiv = document.createElement('div');
        tempDiv.classList.add('stockitem');
        tempDiv.style.width = "".concat(CARD_WIDTH, "px");
        tempDiv.style.height = "".concat(CARD_HEIGHT, "px");
        tempDiv.style.position = "relative";
        tempDiv.style.backgroundImage = "url('".concat(g_gamethemeurl, "img/").concat(this.getImageName(card.type), "-cards.jpg')");
        var imagePosition = ((card.type + card.side) % 100) - 1;
        var image_items_per_row = 10;
        var row = Math.floor(imagePosition / image_items_per_row);
        var xBackgroundPercent = (imagePosition - (row * image_items_per_row)) * 100;
        var yBackgroundPercent = row * 100;
        tempDiv.style.backgroundPosition = "-".concat(xBackgroundPercent, "% -").concat(yBackgroundPercent, "%");
        document.body.appendChild(tempDiv);
        this.setDivAsCard(tempDiv, card.type + (card.side || 0));
        document.body.removeChild(tempDiv);
        return tempDiv;
    };
    CardsManager.prototype.getMimickedCardText = function (mimickedCard) {
        var mimickedCardText = '-';
        if (mimickedCard) {
            var tempDiv = this.generateCardDiv(mimickedCard);
            mimickedCardText = "<br>".concat(tempDiv.outerHTML);
        }
        return mimickedCardText;
    };
    CardsManager.prototype.changeMimicTooltip = function (mimicCardId, mimickedCardText) {
        this.game.addTooltipHtml(mimicCardId, this.getTooltip(27) + "<br>".concat(_('Mimicked card:'), " ").concat(mimickedCardText));
    };
    CardsManager.prototype.placeSuperiorAlienTechnologyTokenOnCard = function (card) {
        var divId = this.getId(card);
        var div = document.getElementById(divId);
        var cardPlaced = div.dataset.placed ? JSON.parse(div.dataset.placed) : { tokens: [] };
        cardPlaced.superiorAlienTechnologyToken = this.getPlaceOnCard(cardPlaced);
        var html = "<div id=\"".concat(divId, "-superior-alien-technology-token\" style=\"left: ").concat(cardPlaced.superiorAlienTechnologyToken.x - 16, "px; top: ").concat(cardPlaced.superiorAlienTechnologyToken.y - 16, "px;\" class=\"card-token ufo token\"></div>");
        dojo.place(html, divId);
        div.dataset.placed = JSON.stringify(cardPlaced);
    };
    return CardsManager;
}(CardManager));
var CurseCardsManager = /** @class */ (function (_super) {
    __extends(CurseCardsManager, _super);
    function CurseCardsManager(game) {
        var _this = _super.call(this, game, {
            animationManager: game.animationManager,
            getId: function (card) { return "curse-card-".concat(card.id); },
            setupDiv: function (card, div) { return div.classList.add('kot-curse-card'); },
            setupFrontDiv: function (card, div) {
                _this.setDivAsCard(div, card.type);
                div.id = "".concat(_super.prototype.getId.call(_this, card), "-front");
                _this.game.addTooltipHtml(div.id, _this.getTooltip(card.type));
            },
            isCardVisible: function (card) { return Boolean(card.type); },
            cardWidth: 132,
            cardHeight: 185,
        }) || this;
        _this.game = game;
        return _this;
    }
    CurseCardsManager.prototype.getCardName = function (cardTypeId) {
        switch (cardTypeId) {
            case 1: return _("Pharaonic Ego");
            case 2: return _("Isis's Disgrace");
            case 3: return _("Thot's Blindness");
            case 4: return _("Tutankhamun's Curse");
            case 5: return _("Buried in Sand");
            case 6: return _("Raging Flood");
            case 7: return _("Hotep's Peace");
            case 8: return _("Set's Storm");
            case 9: return _("Builders' Uprising");
            case 10: return _("Inadequate offering");
            case 11: return _("Bow Before Ra");
            case 12: return _("Vengeance of Horus");
            case 13: return _("Ordeal of the Mighty");
            case 14: return _("Ordeal of the Wealthy");
            case 15: return _("Ordeal of the Spiritual");
            case 16: return _("Resurrection of Osiris");
            case 17: return _("Forbidden Library");
            case 18: return _("Confused Senses");
            case 19: return _("Pharaonic Skin");
            case 20: return _("Khepri's Rebellion");
            case 21: return _("Body, Spirit and Ka");
            case 22: return _("False Blessing");
            case 23: return _("Gaze of the Sphinx");
            case 24: return _("Scribe's Perserverance");
        }
        return null;
    };
    CurseCardsManager.prototype.getPermanentEffect = function (cardTypeId) {
        switch (cardTypeId) {
            case 1: return _("Monsters cannot Yield Tokyo.");
            case 2: return _("Monsters without the Golden Scarab cannot gain [Heart].");
            case 3: return _("Monsters without the Golden Scarab cannot gain [Energy].");
            case 4: return _("Monsters without the Golden Scarab cannot gain [Star].");
            case 5: return _("Monsters have 1 less Roll (minimum 1 Roll).");
            case 6: return _("Monsters roll 1 less die.");
            case 7: return _("Monsters without the Golden Scarab cannot use [diceSmash].");
            case 8: return _("At the start of your turn, lose 1[Heart].");
            case 9: return _("At the start of your turn, lose 2[Star].");
            case 10: return _("Cards cost 2 extra [Energy].");
            case 11: return _("Monsters’ maximum [Heart] is 8[Heart] (Monsters that have more than 8[Heart] go down to 8[Heart]).");
            case 12: return _("Monsters cannot reroll [diceSmash].");
            case 13: return _("At the start of each turn, the Monster(s) with the most [Heart] lose 1[Heart].");
            case 14: return _("At the start of each turn, the Monster(s) with the most [Star] lose 1[Star].");
            case 15: return _("At the start of each turn, the Monster(s) with the most [Energy] lose 1[Energy].");
            case 16: return _("Monsters outside of Tokyo cannot use [diceHeart]. Monsters in Tokyo can use their [diceHeart].");
            case 17: return _("Monsters without the Golden Scarab cannot buy Power cards.");
            case 18: return _("After resolving the die of Fate, the Monster with the Golden Scarab can force you to reroll up to 2 dice of his choice.");
            case 19: return _("The Monster with the Golden Scarab cannot lose [Heart].");
            case 20: return _("At the start of each turn, the Monster with the Golden Scarab must give 1[Heart]/[Energy]/[Star] to the Monster whose turn it is.");
            case 21: return _("Only [diceSmash], [diceHeart] and [diceEnergy] faces can be used.");
            case 22: return _("Monsters roll 2 extra dice and have 1 extra die Roll. After resolving their dice, they lose 1[Heart] for each different face they rolled.");
            case 23: return this.game.isPowerUpExpansion() ? _("[Keep] cards and Permanent Evolution cards have no effect.") : _("[Keep] cards have no effect.");
            case 24: return _("You cannot reroll your [dice1].");
        }
        return null;
    };
    CurseCardsManager.prototype.getAnkhEffect = function (cardTypeId) {
        switch (cardTypeId) {
            case 1: return _("Yield Tokyo. You can’t enter Tokyo this turn.");
            case 2:
            case 3:
            case 4:
            case 7:
            case 17:
            case 18:
            case 19: return _("Take the Golden Scarab.");
            case 5: return _("You have 1 extra die Roll.");
            case 6: return _("Take an extra die and put it on the face of your choice.");
            case 8:
            case 11:
            case 13: return "+2[Heart]";
            case 9: return _("If you are not in Tokyo, play an extra turn after this one without the die of Fate.");
            case 10: return _("Draw a Power card.");
            case 12: return _("Gain 1[Star] for each [diceSmash] you rolled.");
            case 14: return "+2[Star]";
            case 15: return "+2[Energy]";
            case 16: return _("Take control of Tokyo.");
            case 20: return _("Take the Golden Scarab and give it to the Monster of your choice.");
            case 21: return _("Cancel the Curse effect.");
            case 22: return _("Choose up to 2 dice, you can reroll or discard each of these dice.");
            case 23: return this.game.isPowerUpExpansion() ? _("Draw an Evolution card or gain 3[Energy].") : "+3[Energy].";
            case 24: return _("Gain 1[Energy] for each [dice1] you rolled.");
        }
        return null;
    };
    CurseCardsManager.prototype.getSnakeEffect = function (cardTypeId) {
        switch (cardTypeId) {
            case 1: return _("Take control of Tokyo.");
            case 2:
            case 8: return "-1[Heart]";
            case 3: return "-2[Energy]";
            case 4:
            case 9: return "-2[Star]";
            case 5: return _("You cannot use your [diceSmash].");
            case 6: return _("Discard 1 die.");
            case 7: return _("Lose 1[Energy] for each [diceSmash] you rolled.");
            case 10: return _("Discard a [Keep] card.");
            case 11: return "-2[Heart]";
            case 12: return _("Lose 1[Heart] for each [diceSmash] you rolled.");
            case 13: return _("The Monster(s) with the most [Heart] lose 1[Heart].");
            case 14: return _("The Monster(s) with the most [Star] lose 1[Star].");
            case 15: return _("The Monster(s) with the most [Energy] lose 1[Energy].");
            case 16: return _("Yield Tokyo. You can’t enter Tokyo this turn.");
            case 17: return _("Discard a [Keep] card.");
            case 18: return _("The Monster with the Golden Scarab, instead of you, gains all [Heart] and [Energy] that you should have gained this turn.");
            case 19: return _("Give any combination of 2[Heart]/[Energy]/[Star] to the Monster with the Golden Scarab.");
            case 20: return _("Take the Golden Scarab.");
            case 21: return _("Cancel the Curse effect. [diceSmash], [diceHeart] and [diceEnergy] faces cannot be used.");
            case 22: return _("The player on your left chooses two of your dice. Reroll these dice.");
            case 23: return this.game.isPowerUpExpansion() ? _("Discard an Evolution card from your hand or in play or lose 3[Energy].") : "-3[Energy].";
            case 24: return _("Discard 1[dice1]");
        }
        return null;
    };
    CurseCardsManager.prototype.getTooltip = function (cardTypeId) {
        var tooltip = "<div class=\"card-tooltip\">\n            <p><strong>".concat(this.getCardName(cardTypeId), "</strong></p>\n            <p><strong>").concat(_("Permanent effect"), " :</strong> ").concat(formatTextIcons(this.getPermanentEffect(cardTypeId)), "</p>\n            <p><strong>").concat(_("Ankh effect"), " :</strong> ").concat(formatTextIcons(this.getAnkhEffect(cardTypeId)), "</p>\n            <p><strong>").concat(_("Snake effect"), " :</strong> ").concat(formatTextIcons(this.getSnakeEffect(cardTypeId)), "</p>\n        </div>");
        return tooltip;
    };
    CurseCardsManager.prototype.setDivAsCard = function (cardDiv, cardType) {
        cardDiv.classList.add('kot-curse-card');
        var permanentEffect = formatTextIcons(this.getPermanentEffect(cardType));
        var ankhEffect = formatTextIcons(this.getAnkhEffect(cardType));
        var snakeEffect = formatTextIcons(this.getSnakeEffect(cardType));
        cardDiv.innerHTML = "\n        <div class=\"name-wrapper\">\n            <div class=\"outline curse\">".concat(this.getCardName(cardType), "</div>\n            <div class=\"text\">").concat(this.getCardName(cardType), "</div>\n        </div>\n        \n        <div class=\"effect-wrapper permanent-effect-wrapper\"><div class=\"effect-text\">").concat(permanentEffect, "</div></div>\n        <div class=\"effect-wrapper ankh-effect-wrapper\"><div class=\"effect-text\">").concat(ankhEffect, "</div></div>\n        <div class=\"effect-wrapper snake-effect-wrapper\"><div class=\"effect-text\">").concat(snakeEffect, "</div></div>");
        Array.from(cardDiv.getElementsByClassName('effect-wrapper')).forEach(function (wrapperDiv) {
            if (wrapperDiv.children[0].clientHeight > wrapperDiv.clientHeight) {
                wrapperDiv.style.fontSize = '6pt';
            }
        });
        ['permanent', 'ankh', 'snake'].forEach(function (effectType) {
            var effectWrapper = cardDiv.getElementsByClassName("".concat(effectType, "-effect-wrapper"))[0];
            var effectText = effectWrapper.getElementsByClassName('effect-text')[0];
            if (effectText.clientHeight > effectWrapper.clientHeight) {
                effectText.classList.add('overflow', effectType);
            }
        });
    };
    return CurseCardsManager;
}(CardManager));
var MONSTERS_WITH_POWER_UP_CARDS = [1, 2, 3, 4, 5, 6, 7, 8, /*TODOPUKK 11,*/ 13, 14, 15, 18, 61, 62, 63];
var EvolutionCardsManager = /** @class */ (function (_super) {
    __extends(EvolutionCardsManager, _super);
    function EvolutionCardsManager(game) {
        var _this = _super.call(this, game, {
            animationManager: game.animationManager,
            getId: function (card) { return "evolution-card-".concat(card.id); },
            setupDiv: function (card, div) { return div.classList.add('kot-evolution'); },
            setupFrontDiv: function (card, div) {
                div.style.backgroundPositionX = "".concat((MONSTERS_WITH_POWER_UP_CARDS.indexOf(Math.floor(card.type / 10)) + 1) * 100 / MONSTERS_WITH_POWER_UP_CARDS.length, "%");
                _this.setDivAsCard(div, card.type);
                div.id = "".concat(_super.prototype.getId.call(_this, card), "-front");
                _this.game.addTooltipHtml(div.id, _this.getTooltip(card.type));
                if (card.tokens > 0) {
                    _this.placeTokensOnCard(card);
                }
            },
            setupBackDiv: function (card, div) {
                div.style.backgroundPositionX = "0%";
            }
        }) || this;
        _this.game = game;
        _this.EVOLUTION_CARDS_TYPES = game.gamedatas.EVOLUTION_CARDS_TYPES;
        return _this;
    }
    // gameui.evolutionCards.debugSeeAllCards()
    EvolutionCardsManager.prototype.debugSeeAllCards = function () {
        var _this = this;
        var html = "<div id=\"all-evolution-cards\" class=\"evolution-card-stock player-evolution-cards\">";
        MONSTERS_WITH_POWER_UP_CARDS.forEach(function (monster) {
            return html += "<div id=\"all-evolution-cards-".concat(monster, "\" style=\"display: flex; flex-wrap: nowrap;\"></div>");
        });
        html += "</div>";
        dojo.place(html, 'kot-table', 'before');
        MONSTERS_WITH_POWER_UP_CARDS.forEach(function (monster) {
            var evolutionRow = document.getElementById("all-evolution-cards-".concat(monster));
            for (var i = 1; i <= 8; i++) {
                var tempDiv = _this.generateCardDiv({
                    type: monster * 10 + i
                });
                tempDiv.id = "all-evolution-cards-".concat(monster, "-").concat(i);
                evolutionRow.appendChild(tempDiv);
                _this.game.addTooltipHtml(tempDiv.id, _this.getTooltip(monster * 10 + i));
            }
        });
    };
    EvolutionCardsManager.prototype.getColoredCardName = function (cardTypeId) {
        switch (cardTypeId) {
            // Space Penguin : blue 2384c6 grey 4c7c96
            case 11: return _("[2384c6]Freeze [4c7c96]Ray");
            case 12: return _("[2384c6]Miraculous [4c7c96]Catch");
            case 13: return _("[2384c6]Deep [4c7c96]Dive");
            case 14: return _("[2384c6]Cold [4c7c96]Wave");
            case 15: return _("[2384c6]Encased [4c7c96]in Ice");
            case 16: return _("[2384c6]Blizzard");
            case 17: return _("[2384c6]Black [4c7c96]Diamond");
            case 18: return _("[2384c6]Icy [4c7c96]Reflection");
            // Alienoid : orange e39717 brown aa673d
            case 21: return _("[e39717]Alien [aa673d]Scourge");
            case 22: return _("[e39717]Precision [aa673d]Field Support");
            case 23: return _("[e39717]Anger [aa673d]Batteries");
            case 24: return _("[e39717]Adapting [aa673d]Technology");
            case 25: return _("[e39717]Funny Looking [aa673d]But Dangerous");
            case 26: return _("[e39717]Exotic [aa673d]Arms");
            case 27: return _("[e39717]Mothership [aa673d]Support");
            case 28: return _("[e39717]Superior Alien [aa673d]Technology");
            // Cyber Kitty : soft b67392 strong ec008c
            case 31: return _("[b67392]Nine [ec008c]Lives");
            case 32: return _("[b67392]Mega [ec008c]Purr");
            case 33: return _("[b67392]Electro- [ec008c]Scratch");
            case 34: return _("[b67392]Cat [ec008c]Nip");
            case 35: return _("[b67392]Play with your [ec008c]Food");
            case 36: return _("[b67392]Feline [ec008c]Motor");
            case 37: return _("[b67392]Mouse [ec008c]Hunter");
            case 38: return _("[b67392]Meow [ec008c]Missle");
            // The King : dark a2550b light ca6c39
            case 41: return _("[a2550b]Monkey [ca6c39]Rush");
            case 42: return _("[a2550b]Simian [ca6c39]Scamper");
            case 43: return _("[a2550b]Jungle [ca6c39]Frenzy");
            case 44: return _("[a2550b]Giant [ca6c39]Banana");
            case 45: return _("[a2550b]Chest [ca6c39]Thumping");
            case 46: return _("[a2550b]Alpha [ca6c39]Male");
            case 47: return _("[a2550b]I Am [ca6c39]the King!");
            case 48: return _("[a2550b]Twas Beauty [ca6c39]Killed the Beast");
            // Gigazaur : dark 00a651 light bed62f
            case 51: return _("[00a651]Detachable [bed62f]Tail");
            case 52: return _("[00a651]Radioactive [bed62f]Waste");
            case 53: return _("[00a651]Primal [bed62f]Bellow");
            case 54: return _("[00a651]Saurian [bed62f]Adaptability");
            case 55: return _("[00a651]Defender [bed62f]Of Tokyo");
            case 56: return _("[00a651]Heat [bed62f]Vision");
            case 57: return _("[00a651]Gamma [bed62f]Breath");
            case 58: return _("[00a651]Tail [bed62f]Sweep");
            // Meka Dragon : gray a68d83 brown aa673d
            case 61: return _("[a68d83]Mecha [aa673d]Blast");
            case 62: return _("[a68d83]Destructive [aa673d]Analysis");
            case 63: return _("[a68d83]Programmed [aa673d]To Destroy");
            case 64: return _("[a68d83]Tune [aa673d]-Up");
            case 65: return _("[a68d83]Breath [aa673d]of Doom");
            case 66: return _("[a68d83]Lightning [aa673d]Armor");
            case 67: return _("[a68d83]Claws [aa673d]of Steel");
            case 68: return _("[a68d83]Target [aa673d]Acquired");
            // Boogie Woogie : dark 6c5b55 light a68d83
            case 71: return /*_TODOPUHA*/ ("[6c5b55]Boo!");
            case 72: return /*_TODOPUHA*/ ("[6c5b55]Worst [a68d83]Nightmare");
            case 73: return /*_TODOPUHA*/ ("[6c5b55]I Live [a68d83]Under Your Bed");
            case 74: return /*_TODOPUHA*/ ("[6c5b55]Boogie [a68d83]Dance");
            case 75: return /*_TODOPUHA*/ ("[6c5b55]Well of [a68d83]Shadow");
            case 76: return /*_TODOPUHA*/ ("[6c5b55]Woem [a68d83]Invaders");
            case 77: return /*_TODOPUHA*/ ("[6c5b55]Nighlife!");
            case 78: return /*_TODOPUHA*/ ("[6c5b55]Dusk [a68d83]Ritual");
            // Pumpkin Jack : dark de6428 light f7941d
            case 81: return /*_TODOPUHA*/ ("[de6428]Detachable [f7941d]Head");
            case 82: return /*_TODOPUHA*/ ("[de6428]Ignis [f7941d]Fatus");
            case 83: return /*_TODOPUHA*/ ("[de6428]Smashing [f7941d]Pumpkin");
            case 84: return /*_TODOPUHA*/ ("[de6428]Trick [f7941d]or Threat");
            case 85: return /*_TODOPUHA*/ ("[de6428]Bobbing [f7941d]for Apples");
            case 86: return /*_TODOPUHA*/ ("[de6428]Feast [f7941d]of Crows");
            case 87: return /*_TODOPUHA*/ ("[de6428]Scythe");
            case 88: return /*_TODOPUHA*/ ("[de6428]Candy!");
            // Cthulhu
            // Anubis
            // King Kong TODOPUKK color codes
            case 111: return /*_TODOPUKK*/ ("Son of Kong Kiko");
            case 112: return /*_TODOPUKK*/ ("King of Skull Island");
            case 113: return /*_TODOPUKK*/ ("Islander Sacrifice");
            case 114: return /*_TODOPUKK*/ ("Monkey Leap");
            case 115: return /*_TODOPUKK*/ ("It Was Beauty Killed the Beast");
            case 116: return /*_TODOPUKK*/ ("Jet Club");
            case 117: return /*_TODOPUKK*/ ("8th Wonder of the World");
            case 118: return /*_TODOPUKK*/ ("Climb Tokyo Tower");
            // Cybertooth
            // Pandakaï : light 6d6e71 dark 231f20
            case 131: return _("[6d6e71]Panda[231f20]Monium");
            case 132: return _("[6d6e71]Eats, Shoots [231f20]and Leaves");
            case 133: return _("[6d6e71]Bam[231f20]Boozle");
            case 134: return _("[6d6e71]Bear [231f20]Necessities");
            case 135: return _("[6d6e71]Panda [231f20]Express");
            case 136: return _("[6d6e71]Bamboo [231f20]Supply");
            case 137: return _("[6d6e71]Pandarwinism [231f20]Survival of the Cutest");
            case 138: return _("[6d6e71]Yin [231f20]& Yang");
            // cyberbunny : soft b67392 strong ec008c
            case 141: return _("[b67392]Stroke [ec008c]Of Genius");
            case 142: return _("[b67392]Emergency [ec008c]Battery");
            case 143: return _("[b67392]Rabbit's [ec008c]Foot");
            case 144: return _("[b67392]Heart [ec008c]of the Rabbit");
            case 145: return _("[b67392]Secret [ec008c]Laboratory");
            case 146: return _("[b67392]King [ec008c]of the Gizmo");
            case 147: return _("[b67392]Energy [ec008c]Sword");
            case 148: return _("[b67392]Electric [ec008c]Carrot");
            // kraken : blue 2384c6 gray 4c7c96
            case 151: return _("[2384c6]Healing [4c7c96]Rain");
            case 152: return _("[2384c6]Destructive [4c7c96]Wave");
            case 153: return _("[2384c6]Cult [4c7c96]Worshippers");
            case 154: return _("[2384c6]High [4c7c96]Tide");
            case 155: return _("[2384c6]Terror [4c7c96]of the Deep");
            case 156: return _("[2384c6]Eater [4c7c96]of Souls");
            case 157: return _("[2384c6]Sunken [4c7c96]Temple");
            case 158: return _("[2384c6]Mandibles [4c7c96]of Dread");
            // Baby Gigazaur : dark a5416f light f05a7d
            case 181: return /*_TODOPUBG*/ ("[a5416f]My [f05a7d]Toy");
            case 182: return /*_TODOPUBG*/ ("[a5416f]Growing [f05a7d]Fast");
            case 183: return /*_TODOPUBG*/ ("[a5416f]Nurture [f05a7d]the Young");
            case 184: return /*_TODOPUBG*/ ("[a5416f]Tiny [f05a7d]Tail");
            case 185: return /*_TODOPUBG*/ ("[a5416f]Too Cute [f05a7d]to Smash");
            case 186: return /*_TODOPUBG*/ ("[a5416f]So [f05a7d]Small!");
            case 187: return /*_TODOPUBG*/ ("[a5416f]Underrated");
            case 188: return /*_TODOPUBG*/ ("[a5416f]Yummy [f05a7d]Yummy");
            // Gigasnail Hydra : light f68712 dark c73917
            case 611: return /*_TODOMB*/ ("[f68712]Unstoppable [c73917]Hydra");
            case 612: return /*_TODOMB*/ ("[f68712]Unstoppable [c73917]Hydra");
            case 613: return /*_TODOMB*/ ("[c73917]Energy-Infused [f68712]Monster");
            case 614: return /*_TODOMB*/ ("[f68712]Three Times [c73917]As Sturdy");
            case 615: return /*_TODOMB*/ ("[f68712]Scary [c73917]Face");
            case 616: return /*_TODOMB*/ ("[f68712]Three Times [c73917]as Strong");
            case 617: return /*_TODOMB*/ ("[c73917]Thinking [f68712]Face");
            case 618: return /*_TODOMB*/ ("[c73917]Hungry [f68712]Face");
            // MasterMindbug : dark 217764 light 2ea98d
            case 621: return /*_TODOMB*/ ("[217764]Mindbug [2ea98d]Acquisition");
            case 622: return /*_TODOMB*/ ("[217764]Intergalactic [2ea98d]Genius");
            case 623: return /*_TODOMB*/ ("[2ea98d]Superior [217764]Brain");
            case 624: return /*_TODOMB*/ ("[2ea98d]Interdimensional [217764]Portal");
            case 625: return /*_TODOMB*/ ("[2ea98d]Helpful [217764]Mindbug");
            case 626: return /*_TODOMB*/ ("[217764]Mindbugs [2ea98d]Overlord");
            case 627: return /*_TODOMB*/ ("[217764]Mind [2ea98d]Control!");
            case 628: return /*_TODOMB*/ ("[2ea98d]Neutralizing [217764]Look");
            // Sharky Crab-dog Mummypus-Zilla : light e25a32 dark b73d42
            case 631: return /*_TODOMB*/ ("[b73d42]Shark [e25a32]Attack?!");
            case 632: return /*_TODOMB*/ ("[e25a32]Energy [b73d42]Devourer");
            case 633: return /*_TODOMB*/ ("[e25a32]Strange [b73d42]Evolution?!");
            case 634: return /*_TODOMB*/ ("[b73d42]Crab [e25a32]Claw?!");
            case 635: return /*_TODOMB*/ ("[b73d42]Undead [e25a32]Mummy?!");
            case 636: return /*_TODOMB*/ ("[e25a32]Follow [b73d42]the Cubes");
            case 637: return /*_TODOMB*/ ("[b73d42]Poisoned [e25a32]Tentacles?!");
            case 638: return /*_TODOMB*/ ("[e25a32]Chew, [b73d42]Pinch, Catch [e25a32]and Smack");
        }
        return null;
    };
    EvolutionCardsManager.prototype.getCardName = function (cardTypeId, state) {
        var coloredCardName = this.getColoredCardName(cardTypeId);
        if (state == 'text-only') {
            return coloredCardName === null || coloredCardName === void 0 ? void 0 : coloredCardName.replace(/\[(\w+)\]/g, '');
        }
        else if (state == 'span') {
            var first_2 = true;
            return (coloredCardName === null || coloredCardName === void 0 ? void 0 : coloredCardName.replace(/\[(\w+)\]/g, function (index, color) {
                var span = "<span style=\"-webkit-text-stroke-color: #".concat(color, ";\">");
                if (first_2) {
                    first_2 = false;
                }
                else {
                    span = "</span>" + span;
                }
                return span;
            })) + "".concat(first_2 ? '' : '</span>');
        }
        return null;
    };
    EvolutionCardsManager.prototype.getCardDescription = function (cardTypeId) {
        switch (cardTypeId) {
            // Space Penguin
            case 11: return _("When you wound a Monster in <i>Tokyo</i>, give them this card. At the start of their turn, choose a die face. This face has no effect this turn. Take back this card at the end of their turn.");
            case 12: return _("Once per turn, during the Buy Power Cards phase, you can shuffle the discard pile and reveal one card randomly. You can buy this card for 1[Energy] less than the normal price or discard it. Put back the rest of the discard pile.");
            case 13: return _("Look at the top 3 Power cards of the deck. Choose one and play it in front of you for free. Put the other Power cards on the bottom of the deck.");
            case 14: return _("Until your next turn, other Monsters roll with 1 less die.");
            case 15: return _("Spend 1[Energy] to choose one of the dice you rolled. This die is frozen until the beginning of your next turn: it cannot be changed and is used normally by Monsters during the Resolve Dice phase.");
            case 16: return _("Play during your turn. Until the start of your next turn, Monsters only have a single Roll and cannot Yield <i>Tokyo</i>.");
            case 17: return _("Gain 1 extra [Star] each time you take control of <i>Tokyo</i> or choose to stay in <i>Tokyo</i> when you could have Yielded.");
            case 18: return _("Choose an Evolution Card in front of a Monster and put a [snowflakeToken] on it. Icy Reflection becomes a copy of that card as if you had played it. If the copied card is removed from play, discard <i>Icy Reflection</i>.");
            // Alienoid
            case 21: return "+2[Star]";
            case 22: return _("Draw Power cards from the top of the deck until you reveal a [keep] card that costs 4[Energy] or less. Play this card in front of you and discard the other cards you drew.");
            case 23: return _("Gain 1[Energy] for each [Heart] you lost this turn.");
            case 24: return _("Put 3 [alienoidToken] tokens on this card. On your turn, you can remove a [alienoidToken] token to discard the 3 face-up Power cards and reveal 3 new ones. Discard this card when there are no more tokens on it.");
            case 25: return _("If you roll at least [dice2][dice2][dice2] each of the other Monster loses 1[Heart].");
            case 26: return _("Before you roll, you can put 2[Energy] on this card. If you do, and roll at least [diceSmash][diceSmash][diceSmash], you can take back your two [Energy] and make the Monsters you wound lose 2 extra [Heart]. Otherwise you lose your 2[Energy] and lose 2[Heart].");
            case 27: return _("Once during your turn, you can spend 1[Energy] to gain 1[Heart].");
            case 28: return _("You can buy [keep] cards by paying half of their cost (rounding up). When you do so, place a [UfoToken] on it. At the start of you turn, roll a die for each of your [keep] cards with a [UfoToken]. Discard each [keep] card for which you rolled a [diceSmash]. You cannot have more than 3 [keep] cards with [UfoToken] at a time.");
            // Cyber Kitty
            case 31: return _("If you reach [Skull] discard your cards (including your Evolutions), lose all your [Energy] and [Star], and leave <i>Tokyo</i>. Gain 9[Heart], 9[Star], and continue playing.");
            case 32: return _("Each of the other Monsters give you 1[Energy] or 1[Star] if they have any (they choose which to give you).");
            case 33: return _("Each of the other Monsters lose 1[Heart].");
            case 34: return _("Play at the start of your turn. You only have one roll this turn. Double the result.");
            case 35: return _("When you wound a Monster in <i>Tokyo</i>, if they must lose at least 2[Heart], you may make them lose 2[Heart] less and steal 1[Star] and 1[Energy] from them instead.");
            case 36: return _("During other Monsters' Enter Tokyo phases, if <i>Tokyo</i> is empty and you were not inside at the start of the turn, you can enter <i>Tokyo</i> instead of the Monster whose turn it is.");
            case 37: return _("If you roll at least one [dice1], gain 1[Star].");
            case 38: return _("If you roll at least one [dice1], add [diceSmash] to your roll.");
            // The King
            case 41: return _("Play when a Monster who controls <i>Tokyo</i> leaves or is eliminated. Take control of <i>Tokyo</i>.");
            case 42: return _("If you Yield <i>Tokyo</i>, do not lose [Heart]. You can’t lose [Heart] this turn.");
            case 43: return _("Play at the end of your Enter Tokyo phase. If you wounded a Monster who controls <i>Tokyo</i> and you didn't take control of <i>Tokyo</i>, take an extra turn after this one.");
            case 44: return "+2[Heart]";
            case 45: return _("You can force Monsters you wound to Yield <i>Tokyo</i>.");
            case 46: return _("Each turn you wound at least one Monster, gain 1[Star].");
            case 47: return _("Gain 1 extra [Star] if you take control of <i>Tokyo</i> or if you start your turn in <i>Tokyo</i>.");
            case 48: return _("Play when you are in <i>Tokyo</i>. Gain 1[Star] at the end of each Monster’s turn (including yours). Discard this card and lose all your [Star] if you leave <i>Tokyo</i>.");
            // Gigazaur 
            case 51:
            case 143: return _("You can’t lose [Heart] this turn.");
            case 52: return "+2[Energy] +1[Heart].";
            case 53: return _("Each of the other Monsters lose 2[Star].");
            case 54: return _("Choose a die face. Take all dice with this face and flip them to a (single) face of your choice.");
            case 55: return _("If you start your turn in <i>Tokyo</i>, each of the other Monsters lose 1[Star].");
            case 56:
            case 185: return _("Monsters that wound you lose 1[Star].");
            case 57: return _("Once per turn, you can change one of the dice you rolled to [diceSmash].");
            case 58: return _("Once per turn, you can change one of the dice you rolled to [dice1] or [dice2].");
            // Meka Dragon
            case 61: return _("Each Monster you wound this turn loses 2 extra [Heart].");
            case 62: return _("Gain 1[Energy] for each [diceSmash] you rolled this turn.");
            case 63: return _("Gain 3[Star] and 2[Energy] each time another Monster reaches [Skull].");
            case 64: return _("Play before rolling dice. If you are not in <i>Tokyo</i>, skip your turn, gain 4[Heart] and 2[Energy].");
            case 65: return _("When you make Monsters in <i>Tokyo</i> lose at least 1[Heart], Monsters who aren't in <i>Tokyo</i> also lose 1[Heart] each (except you).");
            case 66: return _("When you lose [Heart], you can roll a die for each [Heart] lost. For each [diceSmash] rolled this way, the Monster whose turn it is also loses 1[Heart].");
            case 67: return _("On your turn, if you make another Monster lose at least 3[Heart], they lose 1 extra [Heart].");
            case 68: return _("When a Monster wounds you, you can give them the [targetToken] token. The Monster who has the [targetToken] token loses 1 extra [Heart] each time you make them lose [Heart].");
            // Boogie Woogie
            // TODOPUHA 71
            case 72: return /*_TODOPUHA*/ ("At the beginning of your turn, give 1[Energy] to the <i>Owner</i> of this card or lose 1[Heart]."); // TODOPUHA TOCHECK what if owner dies?
            case 73: return /*_TODOPUHA*/ ("You play with one less die.");
            // TODOPUHA 74
            case 75: return "+2[Heart]";
            case 76: return /*_TODOPUHA*/ ("Each of the other Monsters loses 2[Heart].");
            case 77: return /*_TODOPUHA*/ ("When you enter <i>Tokyo</i>, gain 1[Heart].");
            // TODOPUHA 78
            // Pumpkin Jack 
            case 81: return /*_TODOPUHA*/ ("Every time the <i>Owner</i> of this card wounds you, lose an extra [Heart].");
            case 82: return /*_TODOPUHA*/ ("You have one less Roll each turn.");
            case 83: return /*_TODOPUHA*/ ("All Monsters with 12 or more [Star] lose 2[Heart].");
            case 84: return /*_TODOPUHA*/ ("If you roll [dice1][dice1][dice1], each of the other Monsters must give you 1[Energy] or lose 2[Heart].");
            case 85: return /*_TODOPUHA*/ ("Once per turn, you can buy a Power card for 2[Energy] less. If the Power card that replaces it has an odd cost, discard the one you just bought and regain the [Energy] you spent.");
            case 86: return /*_TODOPUHA*/ ("Each Monster must give you 1[Heart], 1[Star], or 1[Energy].");
            case 87: return /*_TODOPUHA*/ ("When you play this card and each time you eliminate a Monster, put 1[Energy] from the pool on this card. For each [Energy] on this card, add [diceSmash] to your Roll.");
            case 88: return "+1[Heart]<br>" + /*_TODOPUHA*/ ("<strong>Or</strong><br>Play this card when a Monster wounds you. Do not lose [Heart] and give this card to that Monster.");
            // King Kong
            case 111: return /*_TODOPUKK*/ ("Play when you reach 0[Heart]. Gain 4[Heart], leave Tokyo, and continue playing.");
            case 112: return /*_TODOPUKK*/ ("Play when you Yield Tokyo. Gain [Heart] to your maximum amount. Skip your next turn.");
            case 113: return /*_TODOPUKK*/ ("Roll 6 dice. Gain 1[Energy] per [diceEnergy] and 1[Heart] per [diceHeart] rolled (even if you are in Tokyo). If you rolled less than 2 [diceEnergy] and/or [diceHeart], take this card back.");
            case 114: return /*_TODOPUKK*/ ("Play during another Monster's movement phase. If Tokyo is empty, you can take control of it instead of the Monster whose turn it is.");
            case 115: return /*_TODOPUKK*/ ("Take the Beauty card, [King Kong] side up. If you don't have the Beauty card at the start of your turn, you cannot reroll [diceSmash] and you wound only the Monster with the Beauty card.");
            case 116: return /*_TODOPUKK*/ ("If you are in Tokyo, add [diceSmash] to your roll.");
            case 117: return /*_TODOPUKK*/ ("If you roll at least 4 identical faces, gain 1[Star].");
            case 118: return /*_TODOPUKK*/ ("If you are in Tokyo, gain 1[Star] for each [dice1] you roll. If you roll [dice1][dice1][dice1][dice1][dice1][dice1], you win the game.");
            // Pandakaï
            case 131: return _("Gain 6[Energy]. All other Monsters gain 3[Energy].");
            case 132: return _("Play when you enter <i>Tokyo</i>. All Monsters outside of <i>Tokyo</i> lose 2[Heart] each. Gain 1[Energy], then leave <i>Tokyo</i>. No Monster takes your place.");
            case 133: return _("Play when a player buys a Power card. They do not spend [Energy] and cannot buy that card this turn. Choose a different Power card they can afford to buy. They must purchase that card.");
            case 134: return "-1[Star] +2[Energy] +2[Heart].";
            case 135: return _("If you rolled at least [dice1][dice2][dice3][diceHeart][diceSmash][diceEnergy], gain 2[Star] and take another turn.");
            case 136: return _("At the start of your turn, you can put 1[Energy] from the bank on this card OR take all of the [Energy] off this card.");
            case 137: return _("If you roll at least [diceHeart][diceHeart][diceHeart], gain 1[Star]. Also gain 1[Star] for each extra [diceHeart] you roll.");
            case 138: return _("Before resolving your dice, you can choose to flip all your dice to the opposite side.") + "<div>[dice1]\u2194[dice3] &nbsp; [dice2]\u2194[diceHeart] &nbsp; [diceSmash]\u2194[diceEnergy]</div>";
            // Cyber Bunny
            case 141: return _("Gain 1[Energy] for each [Energy] you already gained this turn.");
            case 142: return "+3[Energy]";
            // 143 same as 51
            case 144: return _("Play when another Monster finishes Rolling. Reroll one of this Monster’s dice. Take back <i>Heart of the Rabbit</i> from your discard when you take control of <i>Tokyo</i>.");
            case 145: return _("The price of Power cards you buy is reduced by 1[Energy].");
            case 146: return _("Gain 1[Star] each time you buy a Power card.");
            case 147: return _("Before rolling dice, you can pay 2[Energy]. If you do so and you roll at least 1 [diceSmash], add [diceSmash] to your Roll. Gain 1[Energy] for each [diceSmash] you rolled this turn.");
            case 148: return _("If you are in <i>Tokyo</i>, Monsters you wound lose one extra [Heart] unless they give you 1[Energy].");
            // Kraken
            case 151: return "+2[Heart]";
            case 152: return _("Play when you enter <i>Tokyo</i>. All other Monsters lose 2[Heart].");
            case 153: return _("Gain 1[Star] for each [Heart] gained this turn.");
            case 154: return _("For each [diceHeart] you rolled, add [diceHeart] to your Roll");
            case 155: return _("Roll one die for each [Heart] you lost this turn. Don’t lose [Heart] for each [diceHeart] you roll.");
            case 156: return _("Gain 1[Heart] each time you enter <i>Tokyo</i>. You can have up to 12[Heart] as long as you own this card.");
            case 157: return _("Before rolling dice, if you are not in <i>Tokyo</i>, you can pass your turn to gain 3[Heart] and 3[Energy].");
            case 158: return _("Monsters you wound lose 1[Star].");
            // Baby Gigazaur
            case 181: return /*_TODOPUBG*/ ("Take one of the three face-up Power cards and put it under this card. It is reserved for your purchase. Once purchased, choose another card to reserve."); // TODOPUBG
            case 182: return /*_TODOPUBG*/ ("If you roll no [diceHeart], gain 1[Heart].");
            case 183: return /*_TODOPUBG*/ ("Each Monster who has more [Star] than you has to give you 1[Star].");
            case 184: return /*_TODOPUBG*/ ("Once per turn, you may change two dice you rolled to [dice1].");
            // 185 same as 56
            case 186: return /*_TODOPUBG*/ ("When a Monster wounds you, roll a die for each [diceSmash]. If any of the results is [diceHeart], you lose no [Heart].");
            case 187: return /*_TODOPUBG*/ ("Add 2 [diceSmash] to your Roll.");
            case 188: return "+2[Heart] +1[Energy].";
            // Gigasnail Hydra
            case 611: return /*_TODOMB*/ ("If you reach [Skull], you are not eliminated. Gain 3[Heart], then play with 1 less die for the rest of the game."); // TODOMB
            case 612: return /*_TODOMB*/ ("If you reach [Skull], you are not eliminated. Gain 3[Heart], then play with 1 less die for the rest of the game."); // TODOMB
            case 613: return /*_TODOMB*/ ("Once per turn, you may pay 1[Energy] to discard 2 dice from your result and add any 1 die symbol to your Roll."); // TODOMB
            case 614: return /*TODOMB add TOUGHT*/ /*_TODOMB*/ ("If you must lose exactly 3[Heart], gain 3[Heart] instead."); // TODOMB
            case 615: return /*_TODOMB*/ ("If you have exactly [die3][die3][die3] among the dice in your Roll, steal 1[Star] from the Monster with the most."); // TODOMB
            case 616: return /*_TODOMB*/ ("If you have exactly 3 identical dice among the dice in your Roll, all other Monsters lose 1[Heart] each."); // TODOMB
            case 617: return /*_TODOMB*/ ("If you have exactly [dieHeart][dieHeart][dieHeart] among the dice in your Roll, gain 3[Heart] and the Monster with the least [Heart] gains 1[Heart]."); // TODOMB
            case 618: return /*_TODOMB*/ ("If you have exactly [dieEnergy][dieEnergy][dieEnergy] among the dice in your Roll, the first Power card you buy this turn costs 3[Energy] less."); // TODOMB
            // MasterMindbug
            case 621: return /*_TODOMB*/ ("Pay 6[Energy]: Gain 1 Mindbug token.");
            case 622: return /*_TODOMB*/ ("Once per turn, you may reroll all your [dieEnergy]."); // TODOMB
            case 623: return /*_TODOMB*/ ("Each time another Monster gains 4[Star] or more, gain 1[Star]."); // TODOMB
            case 624: return /*_TODOMB*/ ("When you leave Tokyo, gain 2[Energy] or 2[Heart]."); // TODOMB
            case 625: return /*_TODOMB*/ ("Discard this card when another Monster resolves their dice to gain as many [Heart] and [Energy] as they do."); // TODOMB
            case 626: return /*_TODOMB*/ ("At the start of your turn, other Monsters may declare allegiance to the MasterMindbug. Choose one of them: they give you 4[Star] and gain 1 Mindbug token."); // TODOMB
            case 627: return /*_TODOMB*/ ("When another Monster is about to resolve their dice, choose 3 dice that you reroll."); // TODOMB
            case 628: return /*_TODOMB*/ ("When another Monster is about to resolve their dice, they must discard all their [dieClaw]."); // TODOMB
            // Sharky Crab-dog Mummypus-Zilla
            case 631: return /*TODOMB add SNEAKY*/ /*_TODOMB*/ ("Monsters in Tokyo lose 1 additional[Star]."); // TODOMB
            case 632: return /*_TODOMB*/ ("Once per turn, pay 1[Energy] to change one of your [dieHeart] to [dieSmash]."); // TODOMB
            case 633: return /*_TODOMB*/ ("Draw the top card of another Monster’s Evolution deck."); // TODOMB
            case 634: return /*_TODOMB*/ ("When you wound a Monster, they must discard all their Power cards. They may pay 1[Energy] per card they want to keep."); // TODOMB
            case 635: return /*TODOMB add TOUGH*/ /*_TODOMB*/ ("You may also activate this keyword when an effect makes you “lose [Heart]”."); // TODOMB
            case 636: return /*TODOMB add HUNTER*/ /*_TODOMB*/ ("If you hunt the Monster with the most [Energy], add [dieClaw][dieClaw] to your Roll."); // TODOMB
            case 637: return /*TODOMB add POISON*/ /*_TODOMB*/ ("If you lose 3[Heart] or more, all other Monsters lose 1[Heart] each."); // TODOMB
            case 638: return /*TODOMB add FRENZY*/ /*_TODOMB*/ ("After your FRENZY turn, lose 2[Star], 2[Heart], and 2[Energy]."); // TODOMB
        }
        return null;
    };
    EvolutionCardsManager.prototype.getDistance = function (p1, p2) {
        return Math.sqrt(Math.pow((p1.x - p2.x), 2) + Math.pow((p1.y - p2.y), 2));
    };
    EvolutionCardsManager.prototype.placeMimicOnCard = function (card) {
        var divId = this.getId(card);
        var div = document.getElementById(divId);
        var cardPlaced = div.dataset.placed ? JSON.parse(div.dataset.placed) : { tokens: [] };
        cardPlaced.mimicToken = this.getPlaceOnCard(cardPlaced);
        var html = "<div id=\"".concat(divId, "-mimic-token\" style=\"left: ").concat(cardPlaced.mimicToken.x - 16, "px; top: ").concat(cardPlaced.mimicToken.y - 16, "px;\" class=\"card-token icy-reflection token\"></div>");
        dojo.place(html, divId);
        div.dataset.placed = JSON.stringify(cardPlaced);
    };
    EvolutionCardsManager.prototype.removeMimicOnCard = function (card) {
        var divId = this.getId(card);
        var div = document.getElementById(divId);
        var cardPlaced = div.dataset.placed ? JSON.parse(div.dataset.placed) : { tokens: [] };
        cardPlaced.mimicToken = null;
        if (document.getElementById("".concat(divId, "-mimic-token"))) {
            this.game.fadeOutAndDestroy("".concat(divId, "-mimic-token"));
        }
        div.dataset.placed = JSON.stringify(cardPlaced);
    };
    EvolutionCardsManager.prototype.getPlaceOnCard = function (cardPlaced) {
        var _this = this;
        var newPlace = {
            x: Math.random() * 100 + 16,
            y: Math.random() * 100 + 16,
        };
        var protection = 0;
        var otherPlaces = cardPlaced.tokens.slice();
        if (cardPlaced.mimicToken) {
            otherPlaces.push(cardPlaced.mimicToken);
        }
        while (protection < 1000 && otherPlaces.some(function (place) { return _this.getDistance(newPlace, place) < 32; })) {
            newPlace.x = Math.random() * 100 + 16;
            newPlace.y = Math.random() * 100 + 16;
            protection++;
        }
        return newPlace;
    };
    EvolutionCardsManager.prototype.placeTokensOnCard = function (card, playerId) {
        var divId = this.getId(card);
        var div = document.getElementById(divId);
        if (!div) {
            return;
        }
        var cardPlaced = div.dataset.placed ? JSON.parse(div.dataset.placed) : { tokens: [] };
        var placed = cardPlaced.tokens;
        var cardType = /* TODOPU card.mimicType ||*/ card.type;
        // remove tokens
        for (var i = card.tokens; i < placed.length; i++) {
            if ([136, 87].includes(cardType) && playerId) {
                this.game.slideToObjectAndDestroy("".concat(divId, "-token").concat(i), "energy-counter-".concat(playerId));
            }
            else {
                this.game.fadeOutAndDestroy("".concat(divId, "-token").concat(i));
            }
        }
        placed.splice(card.tokens, placed.length - card.tokens);
        // add tokens
        for (var i = placed.length; i < card.tokens; i++) {
            var newPlace = this.getPlaceOnCard(cardPlaced);
            placed.push(newPlace);
            var html = "<div id=\"".concat(divId, "-token").concat(i, "\" style=\"left: ").concat(newPlace.x - 16, "px; top: ").concat(newPlace.y - 16, "px;\" class=\"card-token ");
            if (cardType === 24) {
                html += "ufo token";
            }
            else if ([26, 136, 87].includes(cardType)) {
                html += "energy-cube cube-shape-".concat(Math.floor(Math.random() * 5));
            }
            html += "\"></div>";
            dojo.place(html, divId);
        }
        div.dataset.placed = JSON.stringify(cardPlaced);
    };
    EvolutionCardsManager.prototype.setDivAsCard = function (cardDiv, cardType) {
        cardDiv.classList.add('kot-evolution');
        var type = this.getCardTypeName(cardType);
        var description = formatTextIcons(this.getCardDescription(cardType).replace(/\[strong\]/g, '<strong>').replace(/\[\/strong\]/g, '</strong>'));
        cardDiv.innerHTML = "\n        <div class=\"evolution-type\">".concat(type, "</div>\n        <div class=\"name-and-description\">\n            <div class=\"name-row\">\n                <div class=\"name-wrapper\">\n                    <div class=\"outline\">").concat(this.getCardName(cardType, 'span'), "</div>\n                    <div class=\"text\">").concat(this.getCardName(cardType, 'text-only'), "</div>\n                </div>\n            </div>\n            <div class=\"description-row\">\n                <div class=\"description-wrapper\">").concat(description, "</div>\n            </div>\n        </div>      \n        ");
        var nameWrapper = cardDiv.getElementsByClassName('name-wrapper')[0];
        var outline = cardDiv.getElementsByClassName('outline')[0];
        var descriptionWrapper = cardDiv.getElementsByClassName('description-wrapper')[0];
        var textHeight = descriptionWrapper.clientHeight;
        var nameHeight = outline.clientHeight;
        if (102 - textHeight < nameHeight) {
            nameWrapper.style.fontSize = '10pt';
            outline.style.webkitTextStroke = '4px #a6c136';
            nameHeight = outline.clientHeight;
        }
        if (102 - textHeight < nameHeight) {
            nameWrapper.style.fontSize = '9pt';
            nameHeight = outline.clientHeight;
        }
        if (textHeight > 80) {
            descriptionWrapper.style.fontSize = '7pt';
            textHeight = descriptionWrapper.clientHeight;
        }
        else {
            return;
        }
        if (textHeight > 80) {
            descriptionWrapper.style.fontSize = '6pt';
            textHeight = descriptionWrapper.clientHeight;
        }
        else {
            return;
        }
        if (102 - textHeight < nameHeight) {
            nameWrapper.style.fontSize = '8pt';
            outline.style.webkitTextStroke = '3px #a6c136';
            nameHeight = outline.clientHeight;
        }
        if (102 - textHeight < nameHeight) {
            nameWrapper.style.fontSize = '7pt';
            outline.style.webkitTextStroke = '3px #a6c136';
            nameHeight = outline.clientHeight;
        }
    };
    EvolutionCardsManager.prototype.getTooltip = function (cardTypeId, ownerId) {
        var tooltip = "<div class=\"card-tooltip\">\n            <p><strong>".concat(this.getCardName(cardTypeId, 'text-only'), "</strong></p>\n            <p>").concat(this.getCardTypeName(cardTypeId), "</p>");
        if (ownerId) {
            var owner = this.game.getPlayer(ownerId);
            tooltip += "<p>".concat(_('Owner:'), " <strong style=\"color: #").concat(owner.color, ";\">").concat(owner.name, "</strong></p>");
        }
        tooltip += "<p>".concat(formatTextIcons(this.getCardDescription(cardTypeId).replace(/\[strong\]/g, '<strong>').replace(/\[\/strong\]/g, '</strong>')), "</p>\n        </div>");
        return tooltip;
    };
    EvolutionCardsManager.prototype.setupNewCard = function (cardDiv, cardType) {
        if (cardType == 0) {
            return;
        }
        this.setDivAsCard(cardDiv, cardType);
        cardDiv.dataset.evolutionId = cardDiv.id.split('_')[2];
        cardDiv.dataset.evolutionType = '' + cardType;
        this.game.addTooltipHtml(cardDiv.id, this.getTooltip(cardType));
    };
    EvolutionCardsManager.prototype.getCardTypeName = function (cardType) {
        var type = this.EVOLUTION_CARDS_TYPES[cardType];
        switch (type) {
            case 1: return _('<strong>Permanent</strong> evolution');
            case 2: return _('<strong>Temporary</strong> evolution');
            case 3: return _('<strong>Gift</strong> evolution');
        }
        return null;
    };
    EvolutionCardsManager.prototype.addCardsToStock = function (stock, cards, from) {
        var _this = this;
        if (!cards.length) {
            return;
        }
        cards.forEach(function (card) {
            stock.addToStockWithId(card.type, "".concat(card.id), from);
            var cardDiv = document.getElementById("".concat(stock.container_div.id, "_item_").concat(card.id));
            _this.game.addTooltipHtml(cardDiv.id, _this.getTooltip(card.type, card.ownerId));
        });
        cards.filter(function (card) { return card.tokens > 0; }).forEach(function (card) { return _this.placeTokensOnCard(card); });
    };
    EvolutionCardsManager.prototype.moveToAnotherStock = function (sourceStock, destinationStock, card) {
        if (sourceStock === destinationStock) {
            return;
        }
        var sourceStockItemId = "".concat(sourceStock.container_div.id, "_item_").concat(card.id);
        if (document.getElementById(sourceStockItemId)) {
            this.addCardsToStock(destinationStock, [card], sourceStockItemId);
            //destinationStock.addToStockWithId(uniqueId, cardId, sourceStockItemId);
            sourceStock.removeFromStockById("".concat(card.id));
        }
        else {
            console.warn("".concat(sourceStockItemId, " not found in "), sourceStock);
            //destinationStock.addToStockWithId(uniqueId, cardId, sourceStock.container_div.id);
            this.addCardsToStock(destinationStock, [card], sourceStock.container_div.id);
        }
        this.game.tableManager.tableHeightChange();
    };
    EvolutionCardsManager.prototype.generateCardDiv = function (card) {
        var tempDiv = document.createElement('div');
        tempDiv.classList.add('stockitem');
        tempDiv.style.width = "".concat(EVOLUTION_SIZE, "px");
        tempDiv.style.height = "".concat(EVOLUTION_SIZE, "px");
        tempDiv.style.position = "relative";
        tempDiv.style.backgroundImage = "url('".concat(g_gamethemeurl, "img/evolution-cards.jpg')");
        var imagePosition = MONSTERS_WITH_POWER_UP_CARDS.indexOf(Math.floor(card.type / 10)) + 1;
        var xBackgroundPercent = imagePosition * 100;
        tempDiv.style.backgroundPosition = "-".concat(xBackgroundPercent, "% 0%");
        document.body.appendChild(tempDiv);
        this.setDivAsCard(tempDiv, card.type);
        document.body.removeChild(tempDiv);
        return tempDiv;
    };
    EvolutionCardsManager.prototype.getMimickedCardText = function (mimickedCard) {
        var mimickedCardText = '-';
        if (mimickedCard) {
            var tempDiv = this.generateCardDiv(mimickedCard);
            mimickedCardText = "<br><div class=\"player-evolution-cards\">".concat(tempDiv.outerHTML, "</div>");
        }
        return mimickedCardText;
    };
    EvolutionCardsManager.prototype.changeMimicTooltip = function (mimicCardId, mimickedCardText) {
        this.game.addTooltipHtml(mimicCardId, this.getTooltip(18) + "<br>".concat(_('Mimicked card:'), " ").concat(mimickedCardText));
    };
    return EvolutionCardsManager;
}(CardManager));
var WICKEDNESS_TILES_WIDTH = 132;
var WICKEDNESS_TILES_HEIGHT = 81;
var WICKEDNESS_LEVELS = [3, 6, 10];
var wickenessTilesIndex = [0, 0, 0, 0, 1, 1, 1, 1, 2, 2];
var WickednessDecks = /** @class */ (function (_super) {
    __extends(WickednessDecks, _super);
    function WickednessDecks(manager) {
        var _this = _super.call(this, manager, null) || this;
        _this.manager = manager;
        _this.decks = [];
        WICKEDNESS_LEVELS.forEach(function (level) {
            dojo.place("<div id=\"wickedness-tiles-pile-".concat(level, "\" class=\"wickedness-tiles-pile wickedness-tile-stock\"></div>"), 'wickedness-board');
            _this.decks[level] = new AllVisibleDeck(manager, document.getElementById("wickedness-tiles-pile-".concat(level)), {
                shift: '3px',
            });
            _this.decks[level].onSelectionChange = function (selection, lastChange) { return _this.selectionChange(selection, lastChange); };
        });
        return _this;
    }
    Object.defineProperty(WickednessDecks.prototype, "onTileClick", {
        set: function (callback) {
            var _this = this;
            WICKEDNESS_LEVELS.forEach(function (level) {
                return _this.decks[level].onCardClick = callback;
            });
        },
        enumerable: false,
        configurable: true
    });
    WickednessDecks.prototype.addCard = function (card, animation) {
        var level = this.getCardLevel(card.type);
        return this.decks[level].addCard(card, animation);
    };
    WickednessDecks.prototype.getCardLevel = function (cardTypeId) {
        var id = cardTypeId % 100;
        if (id > 8) {
            return 10;
        }
        else if (id > 4) {
            return 6;
        }
        else {
            return 3;
        }
    };
    WickednessDecks.prototype.setOpened = function (level, opened) {
        this.decks[level].setOpened(opened);
    };
    WickednessDecks.prototype.setSelectableLevel = function (level) {
        var _this = this;
        WICKEDNESS_LEVELS.forEach(function (l) {
            _this.decks[l].setSelectionMode(l == level ? 'single' : 'none');
        });
    };
    WickednessDecks.prototype.selectionChange = function (selection, lastChange) {
        var _a;
        (_a = this.onSelectionChange) === null || _a === void 0 ? void 0 : _a.call(this, selection, lastChange);
    };
    WickednessDecks.prototype.removeCard = function (card, settings) {
        var _this = this;
        return Promise.all(WICKEDNESS_LEVELS.map(function (l) {
            _this.decks[l].removeCard(card, settings);
        })).then(function () { return true; });
    };
    WickednessDecks.prototype.getStock = function (card) {
        return this.decks[this.getCardLevel(card.type)];
    };
    return WickednessDecks;
}(CardStock));
var WickednessTilesManager = /** @class */ (function (_super) {
    __extends(WickednessTilesManager, _super);
    function WickednessTilesManager(game) {
        var _this = _super.call(this, game, {
            animationManager: game.animationManager,
            getId: function (card) { return "wickedness-tile-".concat(card.id); },
            setupDiv: function (card, div) { return div.classList.add('kot-tile'); },
            setupFrontDiv: function (card, div) {
                div.dataset.color = card.type >= 100 ? 'green' : 'orange';
                div.dataset.level = "".concat(_this.getCardLevel(card.type));
                _this.setDivAsCard(div, card.type);
                div.id = "".concat(_super.prototype.getId.call(_this, card), "-front");
                _this.game.addTooltipHtml(div.id, _this.getTooltip(card.type));
                if (card.tokens > 0) {
                    _this.placeTokensOnTile(card);
                }
            },
            isCardVisible: function () { return true; },
            cardWidth: 132,
            cardHeight: 81,
        }) || this;
        _this.game = game;
        return _this;
    }
    WickednessTilesManager.prototype.debugSeeAllCards = function () {
        var _this = this;
        var html = "<div id=\"all-wickedness-tiles\" class=\"wickedness-tile-stock player-wickedness-tiles\">";
        [0, 1].forEach(function (side) {
            return html += "<div id=\"all-wickedness-tiles-".concat(side, "\" style=\"display: flex; flex-wrap: nowrap;\"></div>");
        });
        html += "</div>";
        dojo.place(html, 'kot-table', 'before');
        [0, 1].forEach(function (side) {
            var evolutionRow = document.getElementById("all-wickedness-tiles-".concat(side));
            for (var i = 1; i <= 10; i++) {
                var tempDiv = _this.generateCardDiv({
                    type: side * 100 + i,
                    side: side
                });
                tempDiv.id = "all-wickedness-tiles-".concat(side, "-").concat(i);
                evolutionRow.appendChild(tempDiv);
                _this.game.addTooltipHtml(tempDiv.id, _this.getTooltip(side * 100 + i));
            }
        });
    };
    WickednessTilesManager.prototype.addCardsToStock = function (stock, cards, from) {
        var _this = this;
        if (!cards.length) {
            return;
        }
        cards.forEach(function (card) {
            var animation = from ? { fromStock: from.getStock(card) } : undefined;
            stock.addCard(card, animation);
        });
        cards.filter(function (card) { return card.tokens > 0; }).forEach(function (card) { return _this.placeTokensOnTile(card); });
    };
    WickednessTilesManager.prototype.generateCardDiv = function (card) {
        var wickednesstilessurl = "".concat(g_gamethemeurl, "img/").concat(this.game.isDarkEdition() ? 'dark/' : '', "wickedness-tiles.jpg");
        var tempDiv = document.createElement('div');
        tempDiv.classList.add('stockitem');
        tempDiv.style.width = "".concat(WICKEDNESS_TILES_WIDTH, "px");
        tempDiv.style.height = "".concat(WICKEDNESS_TILES_HEIGHT, "px");
        tempDiv.style.position = "relative";
        tempDiv.style.backgroundImage = "url('".concat(wickednesstilessurl, "')");
        tempDiv.style.backgroundPosition = "-".concat(wickenessTilesIndex[card.type % 100] * 50, "% ").concat(card.side > 0 ? 100 : 0, "%");
        document.body.appendChild(tempDiv);
        this.setDivAsCard(tempDiv, card.type);
        document.body.removeChild(tempDiv);
        return tempDiv;
    };
    WickednessTilesManager.prototype.getCardLevel = function (cardTypeId) {
        var id = cardTypeId % 100;
        if (id > 8) {
            return 10;
        }
        else if (id > 4) {
            return 6;
        }
        else {
            return 3;
        }
    };
    WickednessTilesManager.prototype.getCardName = function (cardTypeId) {
        switch (cardTypeId) {
            // orange
            case 1: return _("Devious");
            case 2: return _("Eternal");
            case 3: return _("Skulking");
            case 4: return _("Tireless");
            case 5: return _("Cyberbrain");
            case 6: return _("Evil Lair");
            case 7: return _("Full regeneration");
            case 8: return _("Widespread Panic");
            case 9: return _("Antimatter Beam");
            case 10: return _("Skybeam");
            // green
            case 101: return _("Barbs");
            case 102: return _("Final Roar");
            case 103: return _("Poison Spit");
            case 104: return _("Underdog");
            case 105: return _("Defender of Tokyo");
            case 106: return _("Fluxling");
            case 107: return _("Have it all!");
            case 108: return _("Sonic Boomer");
            case 109: return _("Final push");
            case 110: return _("Starburst");
        }
        return null;
    };
    WickednessTilesManager.prototype.getCardDescription = function (cardTypeId) {
        switch (cardTypeId) {
            // orange
            case 1: return _("<strong>Gain one extra die Roll</strong> each turn.");
            case 2: return _("At the start of your turn, <strong> gain 1[Heart].</strong>");
            case 3: return _("When you roll [dice1][dice1][dice1] or more, <strong> gain 1 extra [Star].</strong>");
            case 4: return _("At the start of your turn, <strong> gain 1[Energy].</strong>");
            case 5: return _("You get <strong>1 extra die.</strong>");
            case 6: return _("Buying Power cards <strong>costs you 1 less [energy].</strong>");
            case 7: return _("<strong>You may have up to 12[heart].</strong> Fully heal (to 12) when you gain this tile.");
            case 8: return _("<strong>All other Monsters lose 4[Star],</strong> then discard this tile.");
            case 9: return _("<strong>Double all of your [diceSmash].</strong>");
            case 10: return _("<strong>Gain 1 extra [Energy]</strong> for each [diceEnergy] and <strong>1 extra [Heart]</strong> for each [diceHeart]");
            // green
            case 101: return _("<strong>When you roll at least [diceSmash][diceSmash], gain a [diceSmash].</strong>");
            case 102: return _("If you are eliminated from the game with 16[Star] or more, <strong>you win the game instead.</strong>");
            case 103: return _("Give 1 <i>Poison</i> token to each Monster you Smash with your [diceSmash]. <strong>At the end of their turn, Monsters lose 1[Heart] for each token they have on them.</strong> A token can be discarded by using a [diceHeart] instead of gaining 1[Heart].");
            case 104: return _("<strong>When you smash a Monster,</strong> if that Monster has more [Star] than you, <strong>steal 1[Star]</strong>");
            case 105: return _("When you move into Tokyo or begin yout turn in Tokyo, <strong>all other Monsters lose 1[Star].</strong>");
            case 106: return _("When you gain this, place it in front of a [keep] card of any player. <strong>This tile counts as a copy of that [keep] card.</strong> You can change which card you are copying at the start of your turn.");
            case 107: return _("When you acquire this tile, <strong>gain 1[Star] for each [keep] card you have.</strong> Gain 1[Star] each time you buy any Power card");
            case 108: return _("At the start of your turn, <strong>gain 1[Star].</strong>");
            case 109: return _("<strong>+2[Heart] +2[Energy]</strong><br><br><strong>Take another turn after this one,</strong> then discard this tile.");
            case 110: return _("<strong>+12[Energy]</strong> then discard this tile.");
        }
        return null;
    };
    WickednessTilesManager.prototype.getTooltip = function (cardType) {
        var level = this.getCardLevel(cardType);
        var description = formatTextIcons(this.getCardDescription(cardType).replace(/\[strong\]/g, '<strong>').replace(/\[\/strong\]/g, '</strong>'));
        var tooltip = "<div class=\"card-tooltip\">\n            <p><strong>".concat(this.getCardName(cardType), "</strong></p>\n            <p class=\"level\">").concat(dojo.string.substitute(_("Level : ${level}"), { 'level': level }), "</p>\n            <p>").concat(description, "</p>\n        </div>");
        return tooltip;
    };
    WickednessTilesManager.prototype.setupNewCard = function (cardDiv, cardType) {
        this.setDivAsCard(cardDiv, cardType);
        this.game.addTooltipHtml(cardDiv.id, this.getTooltip(cardType));
    };
    WickednessTilesManager.prototype.setDivAsCard = function (cardDiv, cardType) {
        cardDiv.classList.add('kot-tile');
        var name = this.getCardName(cardType);
        var description = formatTextIcons(this.getCardDescription(cardType).replace(/\[strong\]/g, '<strong>').replace(/\[\/strong\]/g, '</strong>'));
        cardDiv.innerHTML = "\n        <div class=\"name-and-description\">\n            <div>\n                <div class=\"name-wrapper\">\n                    <div class=\"outline ".concat(cardType > 100 ? 'wickedness-tile-side1' : 'wickedness-tile-side0', "\">").concat(name, "</div>\n                    <div class=\"text\">").concat(name, "</div>\n                </div>\n            </div>\n            <div>        \n                <div class=\"description-wrapper\">").concat(description, "</div>\n            </div>\n        ");
        var textHeight = cardDiv.getElementsByClassName('description-wrapper')[0].clientHeight;
        if (textHeight > 50) {
            cardDiv.getElementsByClassName('description-wrapper')[0].style.width = '100%';
        }
        textHeight = cardDiv.getElementsByClassName('description-wrapper')[0].clientHeight;
        if (textHeight > 50) {
            cardDiv.getElementsByClassName('description-wrapper')[0].style.fontSize = '6pt';
        }
        textHeight = cardDiv.getElementsByClassName('description-wrapper')[0].clientHeight;
        var nameHeight = cardDiv.getElementsByClassName('outline')[0].clientHeight;
        if (75 - textHeight < nameHeight) {
            cardDiv.getElementsByClassName('name-wrapper')[0].style.fontSize = '8pt';
        }
        nameHeight = cardDiv.getElementsByClassName('outline')[0].clientHeight;
        if (75 - textHeight < nameHeight) {
            cardDiv.getElementsByClassName('name-wrapper')[0].style.fontSize = '7pt';
        }
    };
    WickednessTilesManager.prototype.changeMimicTooltip = function (mimicCardId, mimickedCardText) {
        this.game.addTooltipHtml(mimicCardId, this.getTooltip(106) + "<br>".concat(_('Mimicked card:'), " ").concat(mimickedCardText));
    };
    WickednessTilesManager.prototype.getDistance = function (p1, p2) {
        return Math.sqrt(Math.pow((p1.x - p2.x), 2) + Math.pow((p1.y - p2.y), 2));
    };
    WickednessTilesManager.prototype.getPlaceOnCard = function (cardPlaced) {
        var _this = this;
        var newPlace = {
            x: Math.random() * 100 + 16,
            y: Math.random() * 50 + 16,
        };
        var protection = 0;
        var otherPlaces = cardPlaced.tokens.slice();
        if (cardPlaced.mimicToken) {
            otherPlaces.push(cardPlaced.mimicToken);
        }
        while (protection < 1000 && otherPlaces.some(function (place) { return _this.getDistance(newPlace, place) < 32; })) {
            newPlace.x = Math.random() * 100 + 16;
            newPlace.y = Math.random() * 50 + 16;
            protection++;
        }
        return newPlace;
    };
    WickednessTilesManager.prototype.placeTokensOnTile = function (tile, playerId) {
        var divId = this.getId(tile);
        var div = document.getElementById(divId);
        if (!div) {
            return;
        }
        var cardPlaced = div.dataset.placed ? JSON.parse(div.dataset.placed) : { tokens: [] };
        var placed = cardPlaced.tokens;
        var cardType = tile.mimicType || tile.type;
        // remove tokens
        for (var i = tile.tokens; i < placed.length; i++) {
            if (cardType === 28 && playerId) {
                this.game.slideToObjectAndDestroy("".concat(divId, "-token").concat(i), "energy-counter-".concat(playerId));
            }
            else {
                this.game.fadeOutAndDestroy("".concat(divId, "-token").concat(i));
            }
        }
        placed.splice(tile.tokens, placed.length - tile.tokens);
        // add tokens
        for (var i = placed.length; i < tile.tokens; i++) {
            var newPlace = this.getPlaceOnCard(cardPlaced);
            placed.push(newPlace);
            var html = "<div id=\"".concat(divId, "-token").concat(i, "\" style=\"left: ").concat(newPlace.x - 16, "px; top: ").concat(newPlace.y - 16, "px;\" class=\"card-token ");
            if (cardType === 28) {
                html += "energy-cube cube-shape-".concat(Math.floor(Math.random() * 5));
            }
            else if (cardType === 41) {
                html += "smoke-cloud token";
            }
            html += "\"></div>";
            dojo.place(html, div.getElementsByClassName('front')[0]);
        }
        div.dataset.placed = JSON.stringify(cardPlaced);
    };
    return WickednessTilesManager;
}(CardManager));
var TokyoTower = /** @class */ (function () {
    function TokyoTower(divId, levels) {
        this.divId = "".concat(divId, "-tokyo-tower");
        var html = "\n        <div id=\"".concat(this.divId, "\" class=\"tokyo-tower tokyo-tower-tooltip\">");
        for (var i = 3; i >= 1; i--) {
            html += "<div id=\"".concat(this.divId, "-level").concat(i, "\">");
            if (levels.includes(i)) {
                html += "<div id=\"tokyo-tower-level".concat(i, "\" class=\"level level").concat(i, "\">");
                if (i == 1 || i == 2) {
                    html += "<div class=\"icon health\"></div>";
                }
                if (i == 2) {
                    html += "<div class=\"icon energy\"></div>";
                }
                if (i == 3) {
                    html += "<div class=\"icon star\"></div>";
                }
                html += "</div>";
            }
            html += "</div>";
        }
        html += "</div>";
        dojo.place(html, divId);
    }
    return TokyoTower;
}());
var isDebug = window.location.host == 'studio.boardgamearena.com' || window.location.hash.indexOf('debug') > -1;
;
var log = isDebug ? console.log.bind(window.console) : function () { };
var POINTS_DEG = [25, 40, 56, 73, 89, 105, 122, 138, 154, 170, 187, 204, 221, 237, 254, 271, 288, 305, 322, 339, 359];
var POINTS_DEG_DARK_EDITION = [44, 62, 76, 91, 106, 121, 136, 148, 161, 174, 189, 205, 224, 239, 256, 275, 292, 309, 327, 342, 359];
var HEALTH_DEG = [360, 326, 301, 274, 249, 226, 201, 174, 149, 122, 98, 64, 39];
var HEALTH_DEG_DARK_EDITION = [360, 332, 305, 279, 255, 230, 204, 177, 153, 124, 101, 69, 48];
var SPLIT_ENERGY_CUBES = 6;
var PlayerTable = /** @class */ (function () {
    function PlayerTable(game, player, playerWithGoldenScarab, evolutionCardsWithSingleState) {
        var _this = this;
        var _a, _b, _c, _d, _e;
        this.game = game;
        this.player = player;
        this.showHand = false;
        this.hiddenEvolutionCards = null;
        this.pickEvolutionCards = null;
        this.playerId = Number(player.id);
        this.playerNo = Number(player.player_no);
        this.monster = Number(player.monster);
        var eliminated = Number(player.eliminated) > 0;
        var html = "\n        <div id=\"player-table-".concat(player.id, "\" class=\"player-table whiteblock ").concat(eliminated ? 'eliminated' : '', "\">\n            <div id=\"player-name-").concat(player.id, "\" class=\"player-name ").concat(game.isDefaultFont() ? 'standard' : 'goodgirl', "\" style=\"color: #").concat(player.color, "\">\n                <div class=\"outline").concat(player.color === '000000' ? ' white' : '', "\">").concat(player.name, "</div>\n                <div class=\"text\">").concat(player.name, "</div>\n            </div> \n            <div id=\"monster-board-wrapper-").concat(player.id, "\" class=\"monster-board-wrapper monster").concat(this.monster, " ").concat(player.location > 0 ? 'intokyo' : '', "\">\n                <div class=\"blue wheel\" id=\"blue-wheel-").concat(player.id, "\"></div>\n                <div class=\"red wheel\" id=\"red-wheel-").concat(player.id, "\"></div>\n                <div class=\"kot-token\"></div>\n                <div id=\"monster-board-").concat(player.id, "\" class=\"monster-board monster").concat(this.monster, "\">\n                    <div id=\"monster-board-").concat(player.id, "-figure-wrapper\" class=\"monster-board-figure-wrapper\">\n                        <div id=\"monster-figure-").concat(player.id, "\" class=\"monster-figure monster").concat(this.monster, "\"><div class=\"stand\"></div></div>\n                    </div>\n                </div>\n                <div id=\"token-wrapper-").concat(this.playerId, "-poison\" class=\"token-wrapper poison\"></div>\n                <div id=\"token-wrapper-").concat(this.playerId, "-shrink-ray\" class=\"token-wrapper shrink-ray\"></div>\n            </div> \n            <div id=\"energy-wrapper-").concat(player.id, "-left\" class=\"energy-wrapper left\"></div>\n            <div id=\"energy-wrapper-").concat(player.id, "-right\" class=\"energy-wrapper right\"></div>\n            <div class=\"cards-stocks\">");
        if (game.isPowerUpExpansion()) {
            html += "\n            <div id=\"visible-evolution-cards-".concat(player.id, "\" class=\"evolution-card-stock player-evolution-cards ").concat(((_a = player.visibleEvolutions) === null || _a === void 0 ? void 0 : _a.length) ? '' : 'empty', "\"></div>\n            ");
            // TODOPUBG
            html += "\n            <div id=\"reserved-cards-".concat(player.id, "\" class=\"reserved card-stock player-cards ").concat(player.cards.length ? '' : 'empty', "\"></div>\n            ");
        }
        if (game.isWickednessExpansion()) {
            html += "<div id=\"wickedness-tiles-".concat(player.id, "\" class=\"wickedness-tile-stock player-wickedness-tiles ").concat(((_b = player.wickednessTiles) === null || _b === void 0 ? void 0 : _b.length) ? '' : 'empty', "\"></div>");
        }
        html += "    <div id=\"cards-".concat(player.id, "\" class=\"card-stock player-cards ").concat(player.reservedCards.length ? '' : 'empty', "\"></div>\n            </div>\n        </div>\n        ");
        dojo.place(html, 'table');
        this.setMonsterFigureBeastMode(((_c = player.cards.find(function (card) { return card.type === 301; })) === null || _c === void 0 ? void 0 : _c.side) === 1);
        this.cards = new LineStock(this.game.cardsManager, document.getElementById("cards-".concat(this.player.id)));
        this.cards.onCardClick = function (card) { return _this.game.onVisibleCardClick(_this.cards, card, _this.playerId); };
        this.cards.addCards(player.cards);
        if (playerWithGoldenScarab) {
            this.takeGoldenScarab();
        }
        if ((_d = player.superiorAlienTechnologyTokens) === null || _d === void 0 ? void 0 : _d.length) {
            player.cards.filter(function (card) { return player.superiorAlienTechnologyTokens.includes(card.id); }).forEach(function (card) { return _this.game.cardsManager.placeSuperiorAlienTechnologyTokenOnCard(card); });
        }
        if (game.isPowerUpExpansion()) {
            // TODOPUBG
            this.reservedCards = new LineStock(this.game.cardsManager, document.getElementById("reserved-cards-".concat(this.player.id)));
            this.cards.onCardClick = function (card) { return _this.game.onVisibleCardClick(_this.reservedCards, card, _this.playerId); };
            this.reservedCards.addCards(player.reservedCards);
        }
        this.initialLocation = Number(player.location);
        this.setPoints(Number(player.score));
        this.setHealth(Number(player.health));
        if (!eliminated) {
            this.setEnergy(Number(player.energy));
            this.setPoisonTokens(Number(player.poisonTokens));
            this.setShrinkRayTokens(Number(player.shrinkRayTokens));
        }
        if (this.game.isKingkongExpansion()) {
            dojo.place("<div id=\"tokyo-tower-".concat(player.id, "\" class=\"tokyo-tower-wrapper\"></div>"), "player-table-".concat(player.id));
            this.tokyoTower = new TokyoTower("tokyo-tower-".concat(player.id), player.tokyoTowerLevels);
        }
        if (this.game.isCybertoothExpansion()) {
            dojo.place("<div id=\"berserk-token-".concat(player.id, "\" class=\"berserk-token berserk-tooltip\" data-visible=\"").concat(player.berserk ? 'true' : 'false', "\"></div>"), "monster-board-".concat(player.id));
        }
        if (this.game.isCthulhuExpansion()) {
            dojo.place("<div id=\"player-table-cultist-tokens-".concat(player.id, "\" class=\"cultist-tokens\"></div>"), "monster-board-".concat(player.id));
            if (!eliminated) {
                this.setCultistTokens(player.cultists);
            }
        }
        if (this.game.isWickednessExpansion()) {
            this.wickednessTiles = new LineStock(this.game.wickednessTilesManager, document.getElementById("wickedness-tiles-".concat(player.id)));
            this.game.wickednessTilesManager.addCardsToStock(this.wickednessTiles, player.wickednessTiles);
        }
        if (game.isPowerUpExpansion()) {
            this.showHand = this.playerId == this.game.getPlayerId();
            if (this.showHand) {
                document.getElementById("hand-wrapper").classList.add('whiteblock');
                dojo.place("\n                <div id=\"pick-evolution\" class=\"evolution-card-stock player-evolution-cards pick-evolution-cards\"></div>\n                <div id=\"hand-evolution-cards-wrapper\">\n                    <div class=\"hand-title\">\n                        <div>\n                            <div id=\"myhand\">".concat(_('My hand'), "</div>\n                        </div>\n                        <div id=\"autoSkipPlayEvolution-wrapper\"></div>\n                    </div>\n                    <div id=\"hand-evolution-cards\" class=\"evolution-card-stock player-evolution-cards\">\n                        <div id=\"empty-message\">").concat(_('Your hand is empty'), "</div>\n                    </div>\n                </div>\n                "), "hand-wrapper");
                this.game.addAutoSkipPlayEvolutionButton();
                this.hiddenEvolutionCards = new LineStock(this.game.evolutionCardsManager, document.getElementById("hand-evolution-cards"));
                this.hiddenEvolutionCards.setSelectionMode('multiple');
                this.hiddenEvolutionCards.onCardClick = function (card) { return _this.game.onHiddenEvolutionClick(card); };
                if (player.hiddenEvolutions) {
                    this.hiddenEvolutionCards.addCards(player.hiddenEvolutions);
                }
                (_e = player.hiddenEvolutions) === null || _e === void 0 ? void 0 : _e.forEach(function (card) {
                    if (evolutionCardsWithSingleState.includes(card.type)) {
                        _this.hiddenEvolutionCards.getCardElement(card).classList.add('disabled');
                    }
                });
                this.checkHandEmpty();
            }
            this.visibleEvolutionCards = new LineStock(this.game.evolutionCardsManager, document.getElementById("visible-evolution-cards-".concat(player.id)));
            this.visibleEvolutionCards.onCardClick = function (card) { return _this.game.onVisibleEvolutionClick(card.id); };
            if (player.visibleEvolutions) {
                this.visibleEvolutionCards.addCards(player.visibleEvolutions);
            }
        }
        if (player.zombified) {
            this.zombify();
        }
    }
    PlayerTable.prototype.initPlacement = function () {
        if (this.initialLocation > 0) {
            this.enterTokyo(this.initialLocation);
        }
    };
    PlayerTable.prototype.enterTokyo = function (location) {
        transitionToObjectAndAttach(this.game, document.getElementById("monster-figure-".concat(this.playerId)), "tokyo-".concat(location == 2 ? 'bay' : 'city'), this.game.getZoom());
    };
    PlayerTable.prototype.leaveTokyo = function () {
        transitionToObjectAndAttach(this.game, document.getElementById("monster-figure-".concat(this.playerId)), "monster-board-".concat(this.playerId, "-figure-wrapper"), this.game.getZoom());
    };
    PlayerTable.prototype.setVisibleCardsSelectionClass = function (visible) {
        document.getElementById("hand-wrapper").classList.toggle('double-selection', visible);
        document.getElementById("player-table-".concat(this.playerId)).classList.toggle('double-selection', visible);
    };
    PlayerTable.prototype.removeCards = function (cards) {
        var _this = this;
        cards.forEach(function (card) { return _this.cards.removeCard(card); });
    };
    PlayerTable.prototype.removeWickednessTiles = function (tiles) {
        var _this = this;
        tiles.forEach(function (tile) { return _this.wickednessTiles.removeCard(tile); });
    };
    PlayerTable.prototype.removeEvolutions = function (cards) {
        var _this = this;
        cards.forEach(function (card) {
            var _a;
            (_a = _this.hiddenEvolutionCards) === null || _a === void 0 ? void 0 : _a.removeCard(card);
            _this.visibleEvolutionCards.removeCard(card);
        });
        this.checkHandEmpty();
    };
    PlayerTable.prototype.setPoints = function (points, delay) {
        var _this = this;
        if (delay === void 0) { delay = 0; }
        var deg = this.monster > 100 ? POINTS_DEG_DARK_EDITION : POINTS_DEG;
        setTimeout(function () { return document.getElementById("blue-wheel-".concat(_this.playerId)).style.transform = "rotate(".concat(deg[Math.min(20, points)], "deg)"); }, delay);
    };
    PlayerTable.prototype.setHealth = function (health, delay) {
        var _this = this;
        if (delay === void 0) { delay = 0; }
        var deg = this.monster > 100 ? HEALTH_DEG_DARK_EDITION : HEALTH_DEG;
        setTimeout(function () { return document.getElementById("red-wheel-".concat(_this.playerId)).style.transform = "rotate(".concat(health > 12 ? 22 : deg[health], "deg)"); }, delay);
    };
    PlayerTable.prototype.setEnergy = function (energy, delay) {
        var _this = this;
        if (delay === void 0) { delay = 0; }
        setTimeout(function () {
            if (_this.game.isKingkongExpansion()) {
                _this.setEnergyOnSide('left', energy);
            }
            else {
                _this.setEnergyOnSide('left', Math.min(energy, SPLIT_ENERGY_CUBES));
                _this.setEnergyOnSide('right', Math.max(energy - SPLIT_ENERGY_CUBES, 0));
            }
        }, delay);
    };
    PlayerTable.prototype.eliminatePlayer = function () {
        var _this = this;
        var _a, _b;
        this.setEnergy(0);
        this.cards.getCards().filter(function (card) { return card.id !== 999; }).forEach(function (card) { return _this.cards.removeCard(card); });
        (_a = this.wickednessTiles) === null || _a === void 0 ? void 0 : _a.removeAll();
        (_b = this.visibleEvolutionCards) === null || _b === void 0 ? void 0 : _b.removeAll();
        if (document.getElementById("monster-figure-".concat(this.playerId))) {
            this.game.fadeOutAndDestroy("monster-figure-".concat(this.playerId));
        }
        if (this.game.isCybertoothExpansion()) {
            this.setBerserk(false);
        }
        dojo.addClass("player-table-".concat(this.playerId), 'eliminated');
    };
    PlayerTable.prototype.setActivePlayer = function (active) {
        dojo.toggleClass("player-table-".concat(this.playerId), 'active', active);
        dojo.toggleClass("overall_player_board_".concat(this.playerId), 'active', active);
    };
    PlayerTable.prototype.setFont = function (prefValue) {
        var defaultFont = prefValue === 1;
        dojo.toggleClass("player-name-".concat(this.playerId), 'standard', defaultFont);
        dojo.toggleClass("player-name-".concat(this.playerId), 'goodgirl', !defaultFont);
    };
    PlayerTable.prototype.getDistance = function (p1, p2) {
        return Math.sqrt(Math.pow((p1.x - p2.x), 2) + Math.pow((p1.y - p2.y), 2));
    };
    PlayerTable.prototype.getPlaceEnergySide = function (placed) {
        var _this = this;
        var newPlace = {
            x: Math.random() * 33 + 16,
            y: Math.random() * 188 + 16,
        };
        var protection = 0;
        while (protection < 1000 && placed.some(function (place) { return _this.getDistance(newPlace, place) < 32; })) {
            newPlace.x = Math.random() * 33 + 16;
            newPlace.y = Math.random() * 188 + 16;
            protection++;
        }
        return newPlace;
    };
    PlayerTable.prototype.setEnergyOnSide = function (side, energy) {
        var divId = "energy-wrapper-".concat(this.playerId, "-").concat(side);
        var div = document.getElementById(divId);
        if (!div) {
            return;
        }
        var placed = div.dataset.placed ? JSON.parse(div.dataset.placed) : [];
        // remove tokens
        for (var i = energy; i < placed.length; i++) {
            this.game.fadeOutAndDestroy("".concat(divId, "-token").concat(i));
        }
        placed.splice(energy, placed.length - energy);
        // add tokens
        for (var i = placed.length; i < energy; i++) {
            var newPlace = this.getPlaceEnergySide(placed);
            placed.push(newPlace);
            var html = "<div id=\"".concat(divId, "-token").concat(i, "\" style=\"left: ").concat(newPlace.x - 16, "px; top: ").concat(newPlace.y - 16, "px;\" class=\"energy-cube cube-shape-").concat(Math.floor(Math.random() * 5), "\"></div>");
            dojo.place(html, divId);
        }
        div.dataset.placed = JSON.stringify(placed);
    };
    PlayerTable.prototype.setMonster = function (monster) {
        var newMonsterClass = "monster".concat(monster);
        dojo.removeClass("monster-figure-".concat(this.playerId), 'monster0');
        dojo.addClass("monster-figure-".concat(this.playerId), newMonsterClass);
        dojo.removeClass("monster-board-".concat(this.playerId), 'monster0');
        dojo.addClass("monster-board-".concat(this.playerId), newMonsterClass);
        dojo.removeClass("monster-board-wrapper-".concat(this.playerId), 'monster0');
        dojo.addClass("monster-board-wrapper-".concat(this.playerId), newMonsterClass);
        var wickednessMarkerDiv = document.getElementById("monster-icon-".concat(this.playerId, "-wickedness"));
        wickednessMarkerDiv === null || wickednessMarkerDiv === void 0 ? void 0 : wickednessMarkerDiv.classList.remove('monster0');
        wickednessMarkerDiv === null || wickednessMarkerDiv === void 0 ? void 0 : wickednessMarkerDiv.classList.add(newMonsterClass);
        if (monster > 100) {
            wickednessMarkerDiv.style.backgroundColor = 'unset';
        }
        this.monster = monster;
        this.setPoints(0);
        this.setHealth(this.game.getPlayerHealth(this.playerId));
    };
    PlayerTable.prototype.getPlaceToken = function (placed) {
        var _this = this;
        var newPlace = {
            x: 16,
            y: Math.random() * 138 + 16,
        };
        var protection = 0;
        while (protection < 1000 && placed.some(function (place) { return _this.getDistance(newPlace, place) < 32; })) {
            newPlace.y = Math.random() * 138 + 16;
            protection++;
        }
        return newPlace;
    };
    PlayerTable.prototype.setTokens = function (type, tokens) {
        var divId = "token-wrapper-".concat(this.playerId, "-").concat(type);
        var div = document.getElementById(divId);
        if (!div) {
            return;
        }
        var placed = div.dataset.placed ? JSON.parse(div.dataset.placed) : [];
        // remove tokens
        for (var i = tokens; i < placed.length; i++) {
            this.game.fadeOutAndDestroy("".concat(divId, "-token").concat(i));
        }
        placed.splice(tokens, placed.length - tokens);
        // add tokens
        for (var i = placed.length; i < tokens; i++) {
            var newPlace = this.getPlaceToken(placed);
            placed.push(newPlace);
            var html = "<div id=\"".concat(divId, "-token").concat(i, "\" style=\"left: ").concat(newPlace.x - 16, "px; top: ").concat(newPlace.y - 16, "px;\" class=\"").concat(type, " token\"></div>");
            dojo.place(html, divId);
            this.game.addTooltipHtml("".concat(divId, "-token").concat(i), type === 'poison' ? this.game.POISON_TOKEN_TOOLTIP : this.game.SHINK_RAY_TOKEN_TOOLTIP);
        }
        div.dataset.placed = JSON.stringify(placed);
    };
    PlayerTable.prototype.setPoisonTokens = function (tokens) {
        this.setTokens('poison', tokens);
    };
    PlayerTable.prototype.setShrinkRayTokens = function (tokens) {
        this.setTokens('shrink-ray', tokens);
    };
    PlayerTable.prototype.getTokyoTower = function () {
        return this.tokyoTower;
    };
    PlayerTable.prototype.setBerserk = function (berserk) {
        document.getElementById("berserk-token-".concat(this.playerId)).dataset.visible = berserk ? 'true' : 'false';
    };
    PlayerTable.prototype.changeForm = function (card) {
        var cardDiv = this.cards.getCardElement(card);
        cardDiv.dataset.side = card.side ? 'back' : 'front';
        this.game.cardsManager.updateFlippableCardTooltip(cardDiv);
        this.setMonsterFigureBeastMode(card.side === 1);
    };
    PlayerTable.prototype.setMonsterFigureBeastMode = function (beastMode) {
        if (this.monster === 12) {
            document.getElementById("monster-figure-".concat(this.playerId)).classList.toggle('beast-mode', beastMode);
        }
    };
    PlayerTable.prototype.setCultistTokens = function (tokens) {
        var containerId = "player-table-cultist-tokens-".concat(this.playerId);
        var container = document.getElementById(containerId);
        while (container.childElementCount > tokens) {
            container.removeChild(container.lastChild);
        }
        for (var i = container.childElementCount; i < tokens; i++) {
            dojo.place("<div id=\"".concat(containerId, "-").concat(i, "\" class=\"cultist-token cultist-tooltip\"></div>"), containerId);
            this.game.addTooltipHtml("".concat(containerId, "-").concat(i), this.game.CULTIST_TOOLTIP);
        }
    };
    PlayerTable.prototype.takeGoldenScarab = function () {
        this.cards.addCard({ id: 999, type: 999 });
    };
    PlayerTable.prototype.showEvolutionPickStock = function (cards) {
        var _this = this;
        if (!this.pickEvolutionCards) {
            this.pickEvolutionCards = new LineStock(this.game.evolutionCardsManager, document.getElementById("pick-evolution"));
            this.pickEvolutionCards.setSelectionMode('single');
            this.pickEvolutionCards.onCardClick = function (card) { return _this.game.chooseEvolutionCardClick(card.id); };
        }
        document.getElementById("pick-evolution").style.display = null;
        this.pickEvolutionCards.addCards(cards);
    };
    PlayerTable.prototype.hideEvolutionPickStock = function () {
        if (this.pickEvolutionCards) {
            document.getElementById("pick-evolution").style.display = 'none';
            this.pickEvolutionCards.removeAll();
        }
    };
    PlayerTable.prototype.playEvolution = function (card, fromStock) {
        if (this.hiddenEvolutionCards) {
            this.visibleEvolutionCards.addCard(card, { fromStock: this.hiddenEvolutionCards });
        }
        else {
            if (fromStock) {
                this.visibleEvolutionCards.addCard(card, { fromStock: fromStock });
            }
            else {
                this.visibleEvolutionCards.addCard(card, { fromElement: document.getElementById("playerhand-counter-wrapper-".concat(this.playerId)) });
            }
        }
        this.game.evolutionCardsManager.getCardElement(card).classList.remove('highlight-evolution');
        this.checkHandEmpty();
    };
    PlayerTable.prototype.highlightHiddenEvolutions = function (cards) {
        var _this = this;
        if (!this.hiddenEvolutionCards) {
            return;
        }
        cards.forEach(function (card) {
            var cardDiv = _this.hiddenEvolutionCards.getCardElement(card);
            cardDiv === null || cardDiv === void 0 ? void 0 : cardDiv.classList.add('highlight-evolution');
        });
    };
    PlayerTable.prototype.unhighlightHiddenEvolutions = function () {
        var _this = this;
        if (!this.hiddenEvolutionCards) {
            return;
        }
        this.hiddenEvolutionCards.getCards().forEach(function (card) {
            var cardDiv = _this.hiddenEvolutionCards.getCardElement(card);
            cardDiv.classList.remove('highlight-evolution');
        });
    };
    PlayerTable.prototype.highlightVisibleEvolutions = function (cards) {
        var _this = this;
        if (!this.visibleEvolutionCards) {
            return;
        }
        cards.forEach(function (card) {
            var cardDiv = _this.visibleEvolutionCards.getCardElement(card);
            cardDiv === null || cardDiv === void 0 ? void 0 : cardDiv.classList.add('highlight-evolution');
        });
    };
    PlayerTable.prototype.unhighlightVisibleEvolutions = function () {
        var _this = this;
        var _a;
        if (!this.visibleEvolutionCards) {
            return;
        }
        (_a = this.visibleEvolutionCards) === null || _a === void 0 ? void 0 : _a.getCards().forEach(function (card) {
            var cardDiv = _this.visibleEvolutionCards.getCardElement(card);
            cardDiv.classList.remove('highlight-evolution');
        });
    };
    PlayerTable.prototype.removeTarget = function () {
        var _a;
        var target = document.getElementById("player-table".concat(this.playerId, "-target"));
        (_a = target === null || target === void 0 ? void 0 : target.parentElement) === null || _a === void 0 ? void 0 : _a.removeChild(target);
    };
    PlayerTable.prototype.giveTarget = function () {
        dojo.place("<div id=\"player-table".concat(this.playerId, "-target\" class=\"target token\"></div>"), "monster-board-".concat(this.playerId));
    };
    PlayerTable.prototype.setEvolutionCardsSingleState = function (evolutionCardsSingleState, enabled) {
        var _this = this;
        this.hiddenEvolutionCards.getCards().forEach(function (card) {
            if (evolutionCardsSingleState.includes(card.type)) {
                _this.hiddenEvolutionCards.getCardElement(card).classList.toggle('disabled', !enabled);
            }
        });
    };
    PlayerTable.prototype.checkHandEmpty = function () {
        if (this.hiddenEvolutionCards) {
            document.getElementById("hand-evolution-cards-wrapper").classList.toggle('empty', this.hiddenEvolutionCards.isEmpty());
        }
    };
    PlayerTable.prototype.zombify = function () {
        var _a;
        (_a = document.querySelector("#cards-".concat(this.player.id, " [data-card-type=\"55\"]"))) === null || _a === void 0 ? void 0 : _a.classList.add('highlight-zombify');
    };
    return PlayerTable;
}());
var PLAYER_TABLE_WIDTH = 420;
var PLAYER_BOARD_HEIGHT = 247;
var CARDS_PER_ROW = 3;
var TABLE_MARGIN = 20;
var PLAYER_TABLE_WIDTH_MARGINS = PLAYER_TABLE_WIDTH + 2 * TABLE_MARGIN;
var PLAYER_BOARD_HEIGHT_MARGINS = PLAYER_BOARD_HEIGHT + 2 * TABLE_MARGIN;
var DISPOSITION_1_COLUMN = [];
var DISPOSITION_2_COLUMNS = [];
var DISPOSITION_3_COLUMNS = [];
DISPOSITION_1_COLUMN[2] = [[0, 1]];
DISPOSITION_1_COLUMN[3] = [[0, 1, 2]];
DISPOSITION_1_COLUMN[4] = [[0, 1, 2, 3]];
DISPOSITION_1_COLUMN[5] = [[0, 1, 2, 3, 4]];
DISPOSITION_1_COLUMN[6] = [[0, 1, 2, 3, 4, 5]];
DISPOSITION_2_COLUMNS[2] = [[0], [1]];
DISPOSITION_2_COLUMNS[3] = [[0], [1, 2]];
DISPOSITION_2_COLUMNS[4] = [[0], [1, 2, 3]];
DISPOSITION_2_COLUMNS[5] = [[0, 4], [1, 2, 3]];
DISPOSITION_2_COLUMNS[6] = [[0, 5], [1, 2, 3, 4]];
DISPOSITION_3_COLUMNS[2] = [[0], [], [1]];
DISPOSITION_3_COLUMNS[3] = [[0, 2], [], [1]];
DISPOSITION_3_COLUMNS[4] = [[0, 3], [], [1, 2]];
DISPOSITION_3_COLUMNS[5] = [[0, 4, 3], [], [1, 2]];
DISPOSITION_3_COLUMNS[6] = [[0, 5, 4], [], [1, 2, 3]];
var ZOOM_LEVELS = [0.25, 0.375, 0.5, 0.625, 0.75, 0.875, 1];
var ZOOM_LEVELS_MARGIN = [-300, -166, -100, -60, -33, -14, 0];
var LOCAL_STORAGE_ZOOM_KEY = 'KingOfTokyo-zoom';
var TableManager = /** @class */ (function () {
    function TableManager(game, playerTables) {
        var _this = this;
        this.game = game;
        this.zoom = 1;
        var zoomStr = localStorage.getItem(LOCAL_STORAGE_ZOOM_KEY);
        if (zoomStr) {
            this.zoom = Number(zoomStr);
        }
        this.setPlayerTables(playerTables);
        this.game.onScreenWidthChange = function () {
            _this.setAutoZoomAndPlacePlayerTables();
            // shift background for mobile
            var backgroundPositionY = 0;
            if (document.body.classList.contains('mobile_version')) {
                backgroundPositionY = 62 + document.getElementById('right-side').getBoundingClientRect().height;
            }
            document.getElementsByTagName(('html'))[0].style.backgroundPositionY = "".concat(backgroundPositionY, "px");
        };
    }
    TableManager.prototype.setPlayerTables = function (playerTables) {
        var currentPlayerId = Number(this.game.getPlayerId());
        var playerTablesOrdered = playerTables.sort(function (a, b) { return a.playerNo - b.playerNo; });
        var playerIndex = playerTablesOrdered.findIndex(function (playerTable) { return playerTable.playerId === currentPlayerId; });
        if (playerIndex > 0) { // not spectator (or 0)            
            this.playerTables = __spreadArray(__spreadArray([], playerTablesOrdered.slice(playerIndex), true), playerTablesOrdered.slice(0, playerIndex), true);
        }
        else { // spectator
            this.playerTables = playerTablesOrdered;
        }
    };
    TableManager.prototype.setAutoZoomAndPlacePlayerTables = function () {
        var _this = this;
        if (dojo.hasClass('kot-table', 'pickMonsterOrEvolutionDeck')) {
            return;
        }
        var zoomWrapperWidth = document.getElementById('zoom-wrapper').clientWidth;
        if (!zoomWrapperWidth) {
            setTimeout(function () { return _this.setAutoZoomAndPlacePlayerTables(); }, 200);
            return;
        }
        var centerTableWidth = document.getElementById('table-center').clientWidth;
        var newZoom = this.zoom;
        while (newZoom > ZOOM_LEVELS[0] && zoomWrapperWidth / newZoom < centerTableWidth) {
            newZoom = ZOOM_LEVELS[ZOOM_LEVELS.indexOf(newZoom) - 1];
        }
        // zoom will also place player tables. we call setZoom even if this method didn't change it because it might have been changed by localStorage zoom
        this.setZoom(newZoom);
    };
    TableManager.prototype.getAvailableColumns = function (tableWidth, tableCenterWidth) {
        if (tableWidth >= tableCenterWidth + 2 * PLAYER_TABLE_WIDTH_MARGINS) {
            return 3;
        }
        else if (tableWidth >= tableCenterWidth + PLAYER_TABLE_WIDTH_MARGINS) {
            return 2;
        }
        else {
            return 1;
        }
    };
    TableManager.prototype.placePlayerTable = function () {
        var _this = this;
        if (dojo.hasClass('kot-table', 'pickMonsterOrEvolutionDeck')) {
            return;
        }
        var players = this.playerTables.length;
        var tableDiv = document.getElementById('table');
        var tableWidth = tableDiv.clientWidth;
        var tableCenterDiv = document.getElementById('table-center');
        var availableColumns = this.getAvailableColumns(tableWidth, tableCenterDiv.clientWidth);
        var columns = Math.min(availableColumns, 3);
        var dispositionModelColumn;
        if (columns === 1) {
            dispositionModelColumn = DISPOSITION_1_COLUMN;
        }
        else if (columns === 2) {
            dispositionModelColumn = DISPOSITION_2_COLUMNS;
        }
        else {
            dispositionModelColumn = DISPOSITION_3_COLUMNS;
        }
        var dispositionModel = dispositionModelColumn[players];
        var disposition = dispositionModel.map(function (columnIndexes) { return columnIndexes.map(function (columnIndex) { return _this.playerTables[columnIndex].playerId; }); });
        var centerColumnIndex = columns === 3 ? 1 : 0;
        // we always compute "center" column first
        var columnOrder;
        if (columns === 1) {
            columnOrder = [0];
        }
        else if (columns === 2) {
            columnOrder = [0, 1];
        }
        else {
            columnOrder = [1, 0, 2];
        }
        columnOrder.forEach(function (columnIndex) {
            var leftColumn = columnIndex === 0 && columns === 3;
            var centerColumn = centerColumnIndex === columnIndex;
            var rightColumn = columnIndex > centerColumnIndex;
            var playerOverTable = centerColumn && disposition[columnIndex].length;
            var dispositionColumn = disposition[columnIndex];
            dispositionColumn.forEach(function (id, index) {
                var playerTableDiv = document.getElementById("player-table-".concat(id));
                var columnId = 'center-column';
                if (rightColumn) {
                    columnId = 'right-column';
                }
                else if (leftColumn) {
                    columnId = 'left-column';
                }
                document.getElementById(columnId).appendChild(playerTableDiv);
                if (centerColumn && playerOverTable && index === 0) {
                    playerTableDiv.after(tableCenterDiv);
                }
            });
        });
        this.tableHeightChange();
    };
    TableManager.prototype.tableHeightChange = function () {
        this.playerTables.forEach(function (playerTable) {
            if (playerTable.visibleEvolutionCards) {
                dojo.toggleClass("visible-evolution-cards-".concat(playerTable.playerId), 'empty', playerTable.visibleEvolutionCards.isEmpty());
            }
            if (playerTable.wickednessTiles) {
                dojo.toggleClass("wickedness-tiles-".concat(playerTable.playerId), 'empty', playerTable.wickednessTiles.isEmpty());
            }
            if (playerTable.reservedCards) {
                dojo.toggleClass("reserved-cards-".concat(playerTable.playerId), 'empty', playerTable.reservedCards.isEmpty());
            }
            dojo.toggleClass("cards-".concat(playerTable.playerId), 'empty', playerTable.cards.isEmpty());
        });
        var zoomWrapper = document.getElementById('zoom-wrapper');
        zoomWrapper.style.height = "".concat(document.getElementById('table').clientHeight * this.zoom, "px");
    };
    TableManager.prototype.setZoom = function (zoom) {
        if (zoom === void 0) { zoom = 1; }
        this.zoom = zoom;
        localStorage.setItem(LOCAL_STORAGE_ZOOM_KEY, '' + this.zoom);
        var newIndex = ZOOM_LEVELS.indexOf(this.zoom);
        dojo.toggleClass('zoom-in', 'disabled', newIndex === ZOOM_LEVELS.length - 1);
        dojo.toggleClass('zoom-out', 'disabled', newIndex === 0);
        var div = document.getElementById('table');
        if (zoom === 1) {
            div.style.transform = '';
            div.style.margin = '';
        }
        else {
            div.style.transform = "scale(".concat(zoom, ")");
            div.style.margin = "0 ".concat(ZOOM_LEVELS_MARGIN[newIndex], "% ").concat((1 - zoom) * -100, "% 0");
        }
        this.placePlayerTable();
    };
    TableManager.prototype.zoomIn = function () {
        if (this.zoom === ZOOM_LEVELS[ZOOM_LEVELS.length - 1]) {
            return;
        }
        var newIndex = ZOOM_LEVELS.indexOf(this.zoom) + 1;
        this.setZoom(ZOOM_LEVELS[newIndex]);
    };
    TableManager.prototype.zoomOut = function () {
        if (this.zoom === ZOOM_LEVELS[0]) {
            return;
        }
        var newIndex = ZOOM_LEVELS.indexOf(this.zoom) - 1;
        this.setZoom(ZOOM_LEVELS[newIndex]);
    };
    return TableManager;
}());
var DieFaceSelector = /** @class */ (function () {
    function DieFaceSelector(nodeId, die, canHealWithDice) {
        var _this = this;
        this.nodeId = nodeId;
        this.dieValue = die.value;
        var colorClass = die.type === 1 ? 'berserk' : (die.extra ? 'green' : 'black');
        var _loop_3 = function (face) {
            var faceId = "".concat(nodeId, "-face").concat(face);
            var html = "<div id=\"".concat(faceId, "\" class=\"die-item dice-icon dice").concat(face, " ").concat(colorClass, " ").concat(this_1.dieValue == face ? 'disabled' : '', "\">");
            if (!die.type && face === 4 && !canHealWithDice) {
                html += "<div class=\"icon forbidden\"></div>";
            }
            html += "</div>";
            dojo.place(html, nodeId);
            document.getElementById(faceId).addEventListener('click', function (event) {
                var _a;
                if (_this.value) {
                    if (_this.value === face) {
                        return;
                    }
                    _this.reset();
                }
                _this.value = face;
                dojo.addClass("".concat(nodeId, "-face").concat(_this.value), 'selected');
                (_a = _this.onChange) === null || _a === void 0 ? void 0 : _a.call(_this, face);
                event.stopImmediatePropagation();
            });
        };
        var this_1 = this;
        for (var face = 1; face <= 6; face++) {
            _loop_3(face);
        }
    }
    DieFaceSelector.prototype.getValue = function () {
        return this.value;
    };
    DieFaceSelector.prototype.reset = function (dieValue) {
        dojo.removeClass("".concat(this.nodeId, "-face").concat(this.value), 'selected');
        if (dieValue && dieValue != this.dieValue) {
            dojo.removeClass("".concat(this.nodeId, "-face").concat(this.dieValue), 'disabled');
            this.dieValue = dieValue;
            dojo.addClass("".concat(this.nodeId, "-face").concat(this.dieValue), 'disabled');
        }
    };
    return DieFaceSelector;
}());
var DIE4_ICONS = [
    null,
    [1, 3, 2],
    [1, 2, 4],
    [1, 4, 3],
    [4, 3, 2],
];
var DICE_STRINGS = [null, '[dice1]', '[dice2]', '[dice3]', '[diceHeart]', '[diceEnergy]', '[diceSmash]'];
var BERSERK_DIE_STRINGS = [null, '[berserkDieEnergy]', '[berserkDieDoubleEnergy]', '[berserkDieSmash]', '[berserkDieSmash]', '[berserkDieDoubleSmash]', '[berserkDieSkull]'];
var DiceManager = /** @class */ (function () {
    function DiceManager(game) {
        this.game = game;
        this.dice = [];
        this.dieFaceSelectors = [];
    }
    DiceManager.prototype.hideLock = function () {
        dojo.addClass('locked-dice', 'hide-lock');
    };
    DiceManager.prototype.showLock = function () {
        dojo.removeClass('locked-dice', 'hide-lock');
    };
    DiceManager.prototype.getDice = function () {
        return this.dice;
    };
    DiceManager.prototype.getBerserkDice = function () {
        return this.dice.filter(function (die) { return die.type === 1; });
    };
    DiceManager.prototype.getLockedDice = function () {
        return this.dice.filter(function (die) { return die.locked; });
    };
    DiceManager.prototype.destroyFreeDice = function () {
        var _this = this;
        var freeDice = this.dice.filter(function (die) { return !die.locked; });
        freeDice.forEach(function (die) { return _this.removeDice(die); });
        return freeDice.map(function (die) { return die.id; });
    };
    DiceManager.prototype.removeAllDice = function () {
        var _this = this;
        this.dice.forEach(function (die) { return _this.removeDice(die); });
        this.clearDiceHtml();
        this.dice = [];
    };
    DiceManager.prototype.setDiceForThrowDice = function (dice, selectableDice, canHealWithDice, frozenFaces) {
        var _this = this;
        var _a;
        this.action = 'move';
        (_a = this.dice) === null || _a === void 0 ? void 0 : _a.forEach(function (die) { return _this.removeDice(die); });
        this.clearDiceHtml();
        this.dice = dice;
        dice.forEach(function (die) { return _this.createDice(die, canHealWithDice, frozenFaces); });
        this.setSelectableDice(selectableDice);
    };
    DiceManager.prototype.disableDiceAction = function () {
        this.setSelectableDice();
        this.action = undefined;
    };
    DiceManager.prototype.getLockedDiceId = function (die) {
        return "locked-dice".concat(this.getDieFace(die));
    };
    DiceManager.prototype.discardDie = function (die) {
        this.removeDice(die, ANIMATION_MS);
    };
    DiceManager.prototype.setDiceForChangeDie = function (dice, selectableDice, args, canHealWithDice, frozenFaces) {
        var _this = this;
        var _a;
        this.action = args.hasHerdCuller || args.hasPlotTwist || args.hasStretchy || args.hasClown || args.hasSaurianAdaptability || args.gammaBreathCardIds.length || args.hasTailSweep || args.hasTinyTail || args.hasBiofuel || args.hasShrinky ? 'change' : null;
        this.changeDieArgs = args;
        if (this.dice.length) {
            this.setSelectableDice(selectableDice);
            return;
        }
        (_a = this.dice) === null || _a === void 0 ? void 0 : _a.forEach(function (die) { return _this.removeDice(die); });
        this.clearDiceHtml();
        this.dice = dice;
        dice.forEach(function (die) {
            _this.createAndPlaceDiceHtml(die, canHealWithDice, frozenFaces, _this.getLockedDiceId(die));
            _this.addDiceRollClass(die);
        });
        this.setSelectableDice(selectableDice);
    };
    DiceManager.prototype.setDiceForDiscardDie = function (dice, selectableDice, canHealWithDice, frozenFaces, action) {
        var _this = this;
        if (action === void 0) { action = 'discard'; }
        this.action = action;
        this.selectedDice = [];
        this.clearDiceHtml();
        this.dice = dice;
        dice.forEach(function (die) {
            _this.createAndPlaceDiceHtml(die, canHealWithDice, frozenFaces, _this.getLockedDiceId(die));
            _this.addDiceRollClass(die);
        });
        this.setSelectableDice(selectableDice);
    };
    DiceManager.prototype.setDiceForSelectHeartAction = function (dice, selectableDice, canHealWithDice, frozenFaces) {
        var _this = this;
        this.action = null;
        if (this.dice.length) {
            return;
        }
        this.clearDiceHtml();
        this.dice = dice;
        dice.forEach(function (die) {
            _this.createAndPlaceDiceHtml(die, canHealWithDice, frozenFaces, _this.getLockedDiceId(die));
            _this.addDiceRollClass(die);
        });
        this.setSelectableDice(selectableDice);
    };
    DiceManager.prototype.setDiceForPsychicProbe = function (dice, selectableDice, canHealWithDice, frozenFaces) {
        var _this = this;
        this.action = 'psychicProbeRoll';
        /*if (this.dice.length) { if active, event are not reset and roll is not applied
            this.setSelectableDice(selectableDice);
            return;
        }*/
        this.clearDiceHtml();
        this.dice = dice;
        dice.forEach(function (die) {
            _this.createAndPlaceDiceHtml(die, canHealWithDice, frozenFaces, _this.getLockedDiceId(die));
            _this.addDiceRollClass(die);
        });
        this.setSelectableDice(selectableDice);
    };
    DiceManager.prototype.changeDie = function (dieId, canHealWithDice, toValue, roll) {
        var die = this.dice.find(function (die) { return die.id == dieId; });
        var divId = "dice".concat(dieId);
        var div = document.getElementById(divId);
        if (div) {
            dojo.removeClass(div, "dice".concat(div.dataset.diceValue));
            div.dataset.diceValue = '' + toValue;
            dojo.addClass(div, "dice".concat(toValue));
            var list = div.getElementsByTagName('ol')[0];
            list.dataset.rollType = roll ? 'odd' : 'change';
            if (roll) {
                this.addDiceRollClass({
                    id: dieId,
                    rolled: roll
                });
            }
            if (!canHealWithDice && !die.type) {
                if (die.value !== 4 && toValue === 4) {
                    dojo.place('<div class="icon forbidden"></div>', divId);
                }
                else if (die.value === 4 && toValue !== 4) {
                    Array.from(div.getElementsByClassName('forbidden')).forEach(function (elem) { return dojo.destroy(elem); });
                }
            }
            list.dataset.roll = '' + toValue;
        }
        if (die) {
            die.value = toValue;
        }
    };
    DiceManager.prototype.showCamouflageRoll = function (dice) {
        var _this = this;
        this.clearDiceHtml();
        dice.forEach(function (dieValue, index) {
            var die = {
                id: index,
                value: dieValue.value,
                extra: false,
                locked: false,
                rolled: dieValue.rolled,
                type: 0,
                canReroll: true,
            };
            _this.createAndPlaceDiceHtml(die, true, [], "dice-selector");
            _this.addDiceRollClass(die);
        });
    };
    DiceManager.prototype.clearDiceHtml = function () {
        var ids = [];
        for (var i = 1; i <= 7; i++) {
            ids.push("locked-dice".concat(i));
        }
        ids.push("locked-dice10", "dice-selector");
        ids.forEach(function (id) {
            var div = document.getElementById(id);
            if (div) {
                div.innerHTML = '';
            }
        });
    };
    DiceManager.prototype.resolveNumberDice = function (args) {
        var _this = this;
        this.dice.filter(function (die) { return die.value === args.diceValue; }).forEach(function (die) { return _this.removeDice(die, 1000, 1500); });
    };
    DiceManager.prototype.resolveHealthDiceInTokyo = function () {
        var _this = this;
        this.dice.filter(function (die) { return die.value === 4; }).forEach(function (die) { return _this.removeDice(die, 1000); });
    };
    DiceManager.prototype.getDieFace = function (die) {
        if (die.type === 2) {
            return 10;
        }
        else if (die.type === 1) {
            if (die.value <= 2) {
                return 5;
            }
            else if (die.value <= 5) {
                return 6;
            }
            else {
                return 7;
            }
        }
        else {
            return die.value;
        }
    };
    DiceManager.prototype.getDiceShowingFace = function (face) {
        var dice = this.dice.filter(function (die) { var _a; return !die.type && die.value === face && ((_a = document.getElementById("dice".concat(die.id))) === null || _a === void 0 ? void 0 : _a.dataset.animated) !== 'true'; });
        if (dice.length > 0 || !this.game.isCybertoothExpansion()) {
            return dice;
        }
        else {
            var berserkDice = this.dice.filter(function (die) { return die.type === 1; });
            if (face == 5) { // energy
                return berserkDice.filter(function (die) { var _a; return die.value >= 1 && die.value <= 2 && ((_a = document.getElementById("dice".concat(die.id))) === null || _a === void 0 ? void 0 : _a.dataset.animated) !== 'true'; });
            }
            else if (face == 6) { // smash
                return berserkDice.filter(function (die) { var _a; return die.value >= 3 && die.value <= 5 && ((_a = document.getElementById("dice".concat(die.id))) === null || _a === void 0 ? void 0 : _a.dataset.animated) !== 'true'; });
            }
            else {
                return [];
            }
        }
    };
    DiceManager.prototype.addDiceAnimation = function (diceValue, number) {
        var _this = this;
        var dice = this.getDiceShowingFace(diceValue);
        if (number) {
            dice = dice.slice(0, number);
        }
        dice.forEach(function (die) {
            document.getElementById("dice".concat(die.id)).dataset.animated !== 'true';
            _this.removeDice(die, 500, 2500);
        });
    };
    DiceManager.prototype.resolveHealthDice = function (number) {
        this.addDiceAnimation(4, number);
    };
    DiceManager.prototype.resolveEnergyDice = function () {
        this.addDiceAnimation(5);
    };
    DiceManager.prototype.resolveSmashDice = function () {
        this.addDiceAnimation(6);
    };
    DiceManager.prototype.toggleLockDice = function (die, event, forcedLockValue) {
        var _this = this;
        if (forcedLockValue === void 0) { forcedLockValue = null; }
        if ((event === null || event === void 0 ? void 0 : event.altKey) || (event === null || event === void 0 ? void 0 : event.ctrlKey)) {
            var dice = [];
            if (event.ctrlKey && event.altKey) { // move everything but die.value dice
                dice = this.dice.filter(function (idie) { return idie.locked === die.locked && _this.getDieFace(idie) !== _this.getDieFace(die); });
            }
            else if (event.ctrlKey) { // move everything with die.value dice
                dice = this.dice.filter(function (idie) { return idie.locked === die.locked && _this.getDieFace(idie) === _this.getDieFace(die); });
            }
            else { // move everything but die
                dice = this.dice.filter(function (idie) { return idie.locked === die.locked && idie.id !== die.id; });
            }
            dice.forEach(function (idie) { return _this.toggleLockDice(idie, null); });
            return;
        }
        if (!die.canReroll) {
            return;
        }
        die.locked = forcedLockValue === null ? !die.locked : forcedLockValue;
        var dieDivId = "dice".concat(die.id);
        var dieDiv = document.getElementById(dieDivId);
        dieDiv.dataset.rolled = 'false';
        var destinationId = die.locked ? this.getLockedDiceId(die) : "dice-selector";
        var tempDestinationId = "temp-destination-wrapper-".concat(destinationId, "-").concat(die.id);
        var tempOriginId = "temp-origin-wrapper-".concat(destinationId, "-").concat(die.id);
        if (document.getElementById(destinationId)) {
            dojo.place("<div id=\"".concat(tempDestinationId, "\" style=\"width: 0px; height: ").concat(dieDiv.clientHeight, "px; display: inline-block; margin: 0;\"></div>"), destinationId);
            dojo.place("<div id=\"".concat(tempOriginId, "\" style=\"width: ").concat(dieDiv.clientWidth, "px; height: ").concat(dieDiv.clientHeight, "px; display: inline-block; margin: -3px 6px 3px -3px;\"></div>"), dieDivId, 'after');
            var destination_1 = document.getElementById(destinationId);
            var tempDestination_1 = document.getElementById(tempDestinationId);
            var tempOrigin_1 = document.getElementById(tempOriginId);
            tempOrigin_1.appendChild(dieDiv);
            dojo.animateProperty({
                node: tempDestinationId,
                properties: {
                    width: dieDiv.clientHeight,
                }
            }).play();
            dojo.animateProperty({
                node: tempOriginId,
                properties: {
                    width: 0,
                }
            }).play();
            dojo.animateProperty({
                node: dieDivId,
                properties: {
                    marginLeft: -13
                }
            }).play();
            slideToObjectAndAttach(this.game, dieDiv, tempDestinationId).then(function () {
                dieDiv.style.marginLeft = '3px';
                if (tempDestination_1.parentElement) { // we only attach if temp div still exists (not deleted)
                    destination_1.append(tempDestination_1.childNodes[0]);
                }
                dojo.destroy(tempDestination_1);
                dojo.destroy(tempOrigin_1);
            });
        }
        this.activateRethrowButton();
        this.game.checkBuyEnergyDrinkState();
        this.game.checkUseSmokeCloudState();
        this.game.checkUseCultistState();
    };
    DiceManager.prototype.lockAll = function () {
        var _this = this;
        var _a;
        (_a = this.dice) === null || _a === void 0 ? void 0 : _a.filter(function (die) { return !die.locked; }).forEach(function (die) { return _this.toggleLockDice(die, null, true); });
    };
    DiceManager.prototype.activateRethrowButton = function () {
        if (document.getElementById('rethrow_button')) {
            dojo.toggleClass('rethrow_button', 'disabled', !this.canRethrow());
        }
    };
    DiceManager.prototype.canRethrow = function () {
        return this.dice.some(function (die) { return !die.locked; });
    };
    DiceManager.prototype.createAndPlaceDie4Html = function (die, destinationId) {
        var html = "\n        <div id=\"dice".concat(die.id, "\" class=\"die4\" data-dice-id=\"").concat(die.id, "\" data-dice-value=\"").concat(die.value, "\">\n            <ol class=\"die-list\" data-roll=\"").concat(die.value, "\">");
        for (var dieFace = 1; dieFace <= 4; dieFace++) {
            html += "<li class=\"face\" data-side=\"".concat(dieFace, "\">");
            DIE4_ICONS[dieFace].forEach(function (icon) { return html += "<span class=\"number face".concat(icon, "\"><div class=\"anubis-icon anubis-icon").concat(icon, "\"></div></span>"); });
            html += "</li>";
        }
        html += "    </ol>";
        if (true) {
            html += "<div class=\"dice-icon die-of-fate\"></div>";
        }
        html += "</div>";
        dojo.place(html, destinationId);
        this.game.addTooltipHtml("dice".concat(die.id), "\n        <strong>".concat(_("Die of Fate effects"), "</strong>\n        <div><div class=\"anubis-icon anubis-icon1\"></div> ").concat(_("Change Curse: Discard the current Curse and reveal the next one."), "</div>\n        <div><div class=\"anubis-icon anubis-icon2\"></div> ").concat(_("No effect. The card's permanent effect remains active, however."), "</div>\n        <div><div class=\"anubis-icon anubis-icon3\"></div> ").concat(_("Suffer the Snake effect."), "</div>\n        <div><div class=\"anubis-icon anubis-icon4\"></div> ").concat(_("Receive the blessing of the Ankh effect."), "</div>\n        "));
    };
    DiceManager.prototype.createAndPlaceDie6Html = function (die, canHealWithDice, frozenFaces, destinationId) {
        var html = "<div id=\"dice".concat(die.id, "\" class=\"dice dice").concat(die.value, "\" data-dice-id=\"").concat(die.id, "\" data-dice-value=\"").concat(die.value, "\">\n        <ol class=\"die-list\" data-roll=\"").concat(die.value, "\">");
        var colorClass = die.type === 1 ? 'berserk' : (die.extra ? 'green' : 'black');
        for (var dieFace = 1; dieFace <= 6; dieFace++) {
            html += "<li class=\"die-item ".concat(colorClass, " side").concat(dieFace, "\" data-side=\"").concat(dieFace, "\"></li>");
        }
        html += "</ol>";
        if (!die.type && (frozenFaces === null || frozenFaces === void 0 ? void 0 : frozenFaces.includes(die.value))) {
            html += "<div class=\"icon frozen\"></div>";
        }
        else if (!die.type && die.value === 4 && !canHealWithDice) {
            html += "<div class=\"icon forbidden\"></div>";
        }
        if (!die.canReroll) {
            html += "<div class=\"icon lock\"></div>";
        }
        html += "</div>";
        // security to destroy pre-existing die with same id
        var dieDiv = document.getElementById("dice".concat(die.id));
        dieDiv === null || dieDiv === void 0 ? void 0 : dieDiv.parentNode.removeChild(dieDiv);
        dojo.place(html, destinationId);
    };
    DiceManager.prototype.createAndPlaceDiceHtml = function (die, canHealWithDice, frozenFaces, destinationId) {
        var _this = this;
        if (die.type == 2) {
            this.createAndPlaceDie4Html(die, destinationId);
        }
        else {
            this.createAndPlaceDie6Html(die, canHealWithDice, frozenFaces, destinationId);
        }
        this.getDieDiv(die).addEventListener('click', function (event) { return _this.dieClick(die, event); });
    };
    DiceManager.prototype.getDieDiv = function (die) {
        return document.getElementById("dice".concat(die.id));
    };
    DiceManager.prototype.createDice = function (die, canHealWithDice, frozenFaces) {
        this.createAndPlaceDiceHtml(die, canHealWithDice, frozenFaces, die.locked ? this.getLockedDiceId(die) : "dice-selector");
        var div = this.getDieDiv(die);
        div.addEventListener('animationend', function (e) {
            if (e.animationName == 'rolled-dice') {
                div.dataset.rolled = 'false';
            }
        });
        this.addDiceRollClass(die);
    };
    DiceManager.prototype.dieClick = function (die, event) {
        if (this.action === 'move') {
            this.toggleLockDice(die, event);
        }
        else if (this.action === 'change') {
            this.toggleBubbleChangeDie(die);
        }
        else if (this.action === 'psychicProbeRoll') {
            this.game.psychicProbeRollDie(die.id);
        }
        else if (this.action === 'discard') {
            this.game.discardDie(die.id);
        }
        else if (this.action === 'rerollOrDiscard') {
            this.game.rerollOrDiscardDie(die.id);
        }
        else if (this.action === 'rerollDice') {
            if (die.type < 2) {
                dojo.toggleClass(this.getDieDiv(die), 'die-selected');
                var selectedDieIndex = this.selectedDice.findIndex(function (d) { return d.id == die.id; });
                if (selectedDieIndex !== -1) {
                    this.selectedDice.splice(selectedDieIndex, 1);
                }
                else {
                    this.selectedDice.push(die);
                }
                this.game.toggleRerollDiceButton();
            }
        }
        else if (this.action === 'freezeDie') {
            this.game.freezeDie(die.id);
        }
    };
    DiceManager.prototype.getSelectedDiceIds = function () {
        return this.selectedDice.map(function (die) { return die.id; });
    };
    DiceManager.prototype.removeSelection = function () {
        var _this = this;
        this.selectedDice.forEach(function (die) { return dojo.removeClass(_this.getDieDiv(die), 'die-selected'); });
        this.selectedDice = [];
    };
    DiceManager.prototype.addRollToDiv = function (dieDiv, rollType, attempt) {
        var _this = this;
        if (attempt === void 0) { attempt = 0; }
        var dieList = dieDiv.getElementsByClassName('die-list')[0];
        if (dieList) {
            dieList.dataset.rollType = rollType;
        }
        else if (attempt < 5) {
            setTimeout(function () { return _this.addRollToDiv(dieDiv, rollType, attempt + 1); }, 200);
        }
    };
    DiceManager.prototype.addDiceRollClass = function (die) {
        var _this = this;
        var dieDiv = this.getDieDiv(die);
        dieDiv.dataset.rolled = die.rolled ? 'true' : 'false';
        if (die.rolled) {
            setTimeout(function () { return _this.addRollToDiv(dieDiv, Math.random() < 0.5 && die.type != 2 ? 'odd' : 'even'); }, 200);
        }
        else {
            this.addRollToDiv(dieDiv, '-');
        }
    };
    DiceManager.prototype.removeDice = function (die, duration, delay) {
        this.dice.splice(this.dice.findIndex(function (d) { return d.id == die.id; }), 1);
        if (duration) {
            this.game.fadeOutAndDestroy("dice".concat(die.id), duration, delay);
        }
        else {
            var dieDiv = document.getElementById("dice".concat(die.id));
            dieDiv === null || dieDiv === void 0 ? void 0 : dieDiv.parentNode.removeChild(dieDiv);
        }
    };
    DiceManager.prototype.hideBubble = function (dieId) {
        var bubble = document.getElementById("discussion_bubble_dice".concat(dieId));
        if (bubble) {
            bubble.style.display = 'none';
            bubble.dataset.visible = 'false';
        }
    };
    DiceManager.prototype.removeAllBubbles = function () {
        this.dieFaceSelectors = [];
        Array.from(document.getElementsByClassName('change-die-discussion_bubble')).forEach(function (elem) { return elem.parentElement.removeChild(elem); });
    };
    DiceManager.prototype.toggleBubbleChangeDie = function (die) {
        var _this = this;
        if (die.type === 2) {
            // die of fate cannot be changed by power cards
            return;
        }
        var divId = "dice".concat(die.id);
        if (!document.getElementById("discussion_bubble_".concat(divId))) {
            dojo.place("<div id=\"discussion_bubble_".concat(divId, "\" class=\"discussion_bubble change-die-discussion_bubble\"></div>"), divId);
        }
        var bubble = document.getElementById("discussion_bubble_".concat(divId));
        var visible = bubble.dataset.visible == 'true';
        if (visible) {
            this.hideBubble(die.id);
        }
        else {
            var bubbleActionButtonsId = "discussion_bubble_".concat(divId, "-action-buttons");
            var bubbleDieFaceSelectorId = "discussion_bubble_".concat(divId, "-die-face-selector");
            var creation = bubble.innerHTML == '';
            if (creation) {
                dojo.place("\n                <div id=\"".concat(bubbleDieFaceSelectorId, "\" class=\"die-face-selector\"></div>\n                <div id=\"").concat(bubbleActionButtonsId, "\" class=\"action-buttons\"></div>\n                "), bubble.id);
            }
            var herdCullerButtonId_1 = "".concat(bubbleActionButtonsId, "-herdCuller");
            var gammaBreathButtonId_1 = "".concat(bubbleActionButtonsId, "-gammaBreath");
            var tailSweepButtonId_1 = "".concat(bubbleActionButtonsId, "-tailSweep");
            var tinyTailButtonId_1 = "".concat(bubbleActionButtonsId, "-tinyTail");
            var plotTwistButtonId_1 = "".concat(bubbleActionButtonsId, "-plotTwist");
            var stretchyButtonId_1 = "".concat(bubbleActionButtonsId, "-stretchy");
            var biofuelButtonId_1 = "".concat(bubbleActionButtonsId, "-biofuel");
            var shrinkyButtonId_1 = "".concat(bubbleActionButtonsId, "-shrinky");
            var saurianAdaptabilityButtonId_1 = "".concat(bubbleActionButtonsId, "-saurianAdaptability");
            var clownButtonId_1 = "".concat(bubbleActionButtonsId, "-clown");
            var args_1 = this.changeDieArgs;
            if (!this.dieFaceSelectors[die.id]) {
                this.dieFaceSelectors[die.id] = new DieFaceSelector(bubbleDieFaceSelectorId, die, args_1.canHealWithDice);
            }
            var dieFaceSelector_1 = this.dieFaceSelectors[die.id];
            if (creation) {
                var buttonText = _("Change die face with ${card_name}");
                if (args_1.hasClown) {
                    this.game.createButton(bubbleActionButtonsId, clownButtonId_1, dojo.string.substitute(buttonText, { 'card_name': "<strong>".concat(this.game.cardsManager.getCardName(212, 'text-only'), "</strong>") }), function () {
                        _this.game.changeDie(die.id, dieFaceSelector_1.getValue(), 212),
                            _this.toggleBubbleChangeDie(die);
                    }, true);
                }
                else {
                    if (args_1.hasHerdCuller) {
                        this.game.createButton(bubbleActionButtonsId, herdCullerButtonId_1, dojo.string.substitute(buttonText, { 'card_name': "<strong>".concat(this.game.cardsManager.getCardName(22, 'text-only'), "</strong>") }), function () {
                            _this.game.changeDie(die.id, dieFaceSelector_1.getValue(), 22);
                            _this.toggleBubbleChangeDie(die);
                        }, true);
                    }
                    if (args_1.gammaBreathCardIds.length) {
                        this.game.createButton(bubbleActionButtonsId, gammaBreathButtonId_1, dojo.string.substitute(buttonText, { 'card_name': "<strong>".concat(this.game.evolutionCardsManager.getCardName(57, 'text-only'), "</strong>") }), function () {
                            _this.game.changeDie(die.id, dieFaceSelector_1.getValue(), 3057, args_1.gammaBreathCardIds[0]);
                            _this.toggleBubbleChangeDie(die);
                        }, true);
                    }
                    if (args_1.hasTailSweep) {
                        this.game.createButton(bubbleActionButtonsId, tailSweepButtonId_1, dojo.string.substitute(buttonText, { 'card_name': "<strong>".concat(this.game.evolutionCardsManager.getCardName(58, 'text-only'), "</strong>") }), function () {
                            _this.game.changeDie(die.id, dieFaceSelector_1.getValue(), 3058);
                            _this.toggleBubbleChangeDie(die);
                        }, true);
                    }
                    if (args_1.hasTinyTail) {
                        this.game.createButton(bubbleActionButtonsId, tinyTailButtonId_1, dojo.string.substitute(buttonText, { 'card_name': "<strong>".concat(this.game.evolutionCardsManager.getCardName(184, 'text-only'), "</strong>") }), function () {
                            _this.game.changeDie(die.id, dieFaceSelector_1.getValue(), 3058);
                            _this.toggleBubbleChangeDie(die);
                        }, true);
                    }
                    if (args_1.hasPlotTwist) {
                        this.game.createButton(bubbleActionButtonsId, plotTwistButtonId_1, dojo.string.substitute(buttonText, { 'card_name': "<strong>".concat(this.game.cardsManager.getCardName(33, 'text-only'), "</strong>") }), function () {
                            _this.game.changeDie(die.id, dieFaceSelector_1.getValue(), 33),
                                _this.toggleBubbleChangeDie(die);
                        }, true);
                    }
                    if (args_1.hasStretchy) {
                        this.game.createButton(bubbleActionButtonsId, stretchyButtonId_1, dojo.string.substitute(buttonText, { 'card_name': "<strong>".concat(this.game.cardsManager.getCardName(44, 'text-only'), "</strong>") }) + formatTextIcons(' (2 [Energy])'), function () {
                            _this.game.changeDie(die.id, dieFaceSelector_1.getValue(), 44),
                                _this.toggleBubbleChangeDie(die);
                        }, true);
                    }
                    if (args_1.hasBiofuel) {
                        this.game.createButton(bubbleActionButtonsId, biofuelButtonId_1, dojo.string.substitute(buttonText, { 'card_name': "<strong>".concat(this.game.cardsManager.getCardName(56, 'text-only'), "</strong>") }), function () {
                            _this.game.changeDie(die.id, dieFaceSelector_1.getValue(), 56),
                                _this.toggleBubbleChangeDie(die);
                        }, true);
                    }
                    if (args_1.hasShrinky) {
                        this.game.createButton(bubbleActionButtonsId, shrinkyButtonId_1, dojo.string.substitute(buttonText, { 'card_name': "<strong>".concat(this.game.cardsManager.getCardName(65, 'text-only'), "</strong>") }), function () {
                            _this.game.changeDie(die.id, dieFaceSelector_1.getValue(), 65),
                                _this.toggleBubbleChangeDie(die);
                        }, true);
                    }
                    if (args_1.hasSaurianAdaptability) {
                        var saurianAdaptabilityButtonLabel = dojo.string.substitute(_("Change all ${die_face} with ${card_name}"), {
                            'card_name': "<strong>".concat(this.game.evolutionCardsManager.getCardName(54, 'text-only'), "</strong>"),
                            'die_face': formatTextIcons(DICE_STRINGS[die.value]),
                        });
                        this.game.createButton(bubbleActionButtonsId, saurianAdaptabilityButtonId_1, saurianAdaptabilityButtonLabel, function () {
                            _this.game.changeDie(die.id, dieFaceSelector_1.getValue(), 3054),
                                _this.toggleBubbleChangeDie(die);
                        }, true);
                    }
                }
                dieFaceSelector_1.onChange = function (value) {
                    if (args_1.hasClown) {
                        dojo.toggleClass(clownButtonId_1, 'disabled', value < 1);
                    }
                    else {
                        if (args_1.hasHerdCuller && die.value != 1) {
                            dojo.toggleClass(herdCullerButtonId_1, 'disabled', value != 1);
                        }
                        if (args_1.gammaBreathCardIds.length && die.value != 6) {
                            dojo.toggleClass(gammaBreathButtonId_1, 'disabled', value != 6);
                        }
                        if (args_1.hasTailSweep) {
                            dojo.toggleClass(tailSweepButtonId_1, 'disabled', value != 1 && value != 2);
                        }
                        if (args_1.hasTinyTail && die.value != 1) {
                            dojo.toggleClass(tinyTailButtonId_1, 'disabled', value != 1);
                        }
                        if (args_1.hasPlotTwist) {
                            dojo.toggleClass(plotTwistButtonId_1, 'disabled', value < 1);
                        }
                        if (args_1.hasStretchy) {
                            var couldUseStretchy = value >= 1;
                            dojo.toggleClass(stretchyButtonId_1, 'disabled', !couldUseStretchy || _this.game.getPlayerEnergy(args_1.playerId) < 2);
                            if (couldUseStretchy) {
                                document.getElementById(stretchyButtonId_1).dataset.enableAtEnergy = '2';
                            }
                            else {
                                document.getElementById(stretchyButtonId_1).removeAttribute('data-enable-at-energy');
                            }
                        }
                        if (args_1.hasBiofuel && die.value == 4) {
                            dojo.toggleClass(biofuelButtonId_1, 'disabled', value != 5);
                        }
                        if (args_1.hasShrinky && die.value == 2) {
                            dojo.toggleClass(shrinkyButtonId_1, 'disabled', value != 1);
                        }
                        if (args_1.hasSaurianAdaptability) {
                            dojo.removeClass(saurianAdaptabilityButtonId_1, 'disabled');
                        }
                    }
                };
                bubble.addEventListener('click', function (event) { return event.stopImmediatePropagation(); });
            }
            if (die.value == dieFaceSelector_1.getValue()) {
                dieFaceSelector_1.reset(die.value);
                if (args_1.hasClown) {
                    dojo.addClass(stretchyButtonId_1, 'disabled');
                }
                else {
                    if (args_1.hasHerdCuller) {
                        dojo.addClass(herdCullerButtonId_1, 'disabled');
                    }
                    if (args_1.gammaBreathCardIds.length) {
                        dojo.addClass(gammaBreathButtonId_1, 'disabled');
                    }
                    if (args_1.hasTailSweep) {
                        dojo.addClass(tailSweepButtonId_1, 'disabled');
                    }
                    if (args_1.hasTinyTail) {
                        dojo.addClass(tinyTailButtonId_1, 'disabled');
                    }
                    if (args_1.hasPlotTwist) {
                        dojo.addClass(plotTwistButtonId_1, 'disabled');
                    }
                    if (args_1.hasStretchy) {
                        dojo.addClass(stretchyButtonId_1, 'disabled');
                    }
                    if (args_1.hasSaurianAdaptability) {
                        dojo.addClass(saurianAdaptabilityButtonId_1, 'disabled');
                    }
                    if (args_1.hasBiofuel) {
                        dojo.addClass(biofuelButtonId_1, 'disabled');
                    }
                    if (args_1.hasShrinky) {
                        dojo.addClass(shrinkyButtonId_1, 'disabled');
                    }
                }
            }
            args_1.dice.filter(function (idie) { return idie.id != die.id; }).forEach(function (idie) { return _this.hideBubble(idie.id); });
            bubble.style.display = 'block';
            bubble.dataset.visible = 'true';
        }
    };
    DiceManager.prototype.setSelectableDice = function (selectableDice) {
        var _this = this;
        if (selectableDice === void 0) { selectableDice = null; }
        var playerIsActive = this.game.isCurrentPlayerActive();
        this.dice.forEach(function (die) { return _this.getDieDiv(die).classList.toggle('selectable', playerIsActive && (selectableDice === null || selectableDice === void 0 ? void 0 : selectableDice.some(function (d) { return d.id == die.id; }))); });
    };
    return DiceManager;
}());
var SPACE_BETWEEN_ANIMATION_AT_START = 43;
var ANIMATION_FULL_SIZE = 220;
var KingOfTokyoAnimationManager = /** @class */ (function () {
    function KingOfTokyoAnimationManager(game, diceManager) {
        this.game = game;
        this.diceManager = diceManager;
    }
    KingOfTokyoAnimationManager.prototype.getDice = function (dieValue) {
        var dice = this.diceManager.getDice();
        var filteredDice = this.getDiceShowingFace(dice, dieValue);
        return filteredDice.length ? filteredDice : dice;
    };
    KingOfTokyoAnimationManager.prototype.resolveNumberDice = function (args) {
        var dice = this.getDice(args.diceValue);
        this.game.displayScoring("dice".concat((dice[Math.floor(dice.length / 2)] || dice[0]).id), this.game.getPreferencesManager().getDiceScoringColor(), args.deltaPoints, 1500);
    };
    KingOfTokyoAnimationManager.prototype.getDiceShowingFace = function (allDice, face) {
        var dice = allDice.filter(function (die) { var _a; return !die.type && ((_a = document.getElementById("dice".concat(die.id))) === null || _a === void 0 ? void 0 : _a.dataset.animated) !== 'true'; });
        if (dice.length > 0 || !this.game.isCybertoothExpansion()) {
            return dice;
        }
        else {
            var berserkDice = this.diceManager.getBerserkDice();
            if (face == 5) { // energy
                return berserkDice.filter(function (die) { var _a; return die.value >= 1 && die.value <= 2 && ((_a = document.getElementById("dice".concat(die.id))) === null || _a === void 0 ? void 0 : _a.dataset.animated) !== 'true'; });
            }
            else if (face == 6) { // smash
                return berserkDice.filter(function (die) { var _a; return die.value >= 3 && die.value <= 5 && ((_a = document.getElementById("dice".concat(die.id))) === null || _a === void 0 ? void 0 : _a.dataset.animated) !== 'true'; });
            }
            else {
                return [];
            }
        }
    };
    KingOfTokyoAnimationManager.prototype.addDiceAnimation = function (diceValue, playerIds, number, targetToken) {
        var _this = this;
        if (document.visibilityState === 'hidden' || this.game.instantaneousMode) {
            return;
        }
        var dice = this.getDice(diceValue);
        var originTop = (document.getElementById(dice[0] ? "dice".concat(dice[0].id) : 'dice-selector') || document.getElementById('dice-selector')).getBoundingClientRect().top;
        var leftDieBR = (document.getElementById(dice[0] ? "dice".concat(dice[0].id) : 'dice-selector') || document.getElementById('dice-selector')).getBoundingClientRect();
        var rightDieBR = (document.getElementById(dice.length ? "dice".concat(dice[dice.length - 1].id) : 'dice-selector') || document.getElementById('dice-selector')).getBoundingClientRect();
        var originCenter = (leftDieBR.left + rightDieBR.right) / 2;
        playerIds.forEach(function (playerId) {
            var maxSpaces = SPACE_BETWEEN_ANIMATION_AT_START * number;
            var halfMaxSpaces = maxSpaces / 2;
            var shift = targetToken ? 16 : 59;
            var _loop_4 = function (i) {
                var originLeft = originCenter - halfMaxSpaces + SPACE_BETWEEN_ANIMATION_AT_START * i;
                var animationId = "animation".concat(diceValue, "-").concat(i, "-player").concat(playerId, "-").concat(new Date().getTime());
                dojo.place("<div id=\"".concat(animationId, "\" class=\"animation animation").concat(diceValue, "\" style=\"left: ").concat(originLeft + window.scrollX - 94, "px; top: ").concat(originTop + window.scrollY - 94, "px;\"></div>"), document.body);
                var animationDiv = document.getElementById(animationId);
                setTimeout(function () {
                    var middleIndex = number / 2;
                    var deltaX = (i - middleIndex) * ANIMATION_FULL_SIZE;
                    animationDiv.style.transform = "translate(".concat(deltaX, "px, 100px) scale(1)");
                }, 50);
                setTimeout(function () {
                    var _a, _b;
                    var targetId = "monster-figure-".concat(playerId);
                    if (targetToken) {
                        var tokensDivs = document.querySelectorAll("div[id^='token-wrapper-".concat(playerId, "-").concat(targetToken, "-token'"));
                        targetId = tokensDivs[tokensDivs.length - (i + 1)].id;
                    }
                    var destination = (_a = document.getElementById(targetId)) === null || _a === void 0 ? void 0 : _a.getBoundingClientRect();
                    if (destination) {
                        var deltaX = destination.left - originLeft + shift * _this.game.getZoom();
                        var deltaY = destination.top - originTop + shift * _this.game.getZoom();
                        animationDiv.style.transition = "transform 0.5s ease-in";
                        animationDiv.style.transform = "translate(".concat(deltaX, "px, ").concat(deltaY, "px) scale(").concat(0.3 * _this.game.getZoom(), ")");
                        animationDiv.addEventListener('transitionend', function () { var _a; return (_a = animationDiv === null || animationDiv === void 0 ? void 0 : animationDiv.parentElement) === null || _a === void 0 ? void 0 : _a.removeChild(animationDiv); });
                        // security
                        setTimeout(function () { var _a; return (_a = animationDiv === null || animationDiv === void 0 ? void 0 : animationDiv.parentElement) === null || _a === void 0 ? void 0 : _a.removeChild(animationDiv); }, 1050);
                    }
                    else {
                        // in case the player dies when starting the animation
                        (_b = animationDiv === null || animationDiv === void 0 ? void 0 : animationDiv.parentElement) === null || _b === void 0 ? void 0 : _b.removeChild(animationDiv);
                    }
                }, 1000);
            };
            for (var i = 0; i < number; i++) {
                _loop_4(i);
            }
        });
    };
    KingOfTokyoAnimationManager.prototype.resolveHealthDice = function (playerId, number, targetToken) {
        this.addDiceAnimation(4, [playerId], number, targetToken);
    };
    KingOfTokyoAnimationManager.prototype.resolveEnergyDice = function (args) {
        this.addDiceAnimation(5, [args.playerId], args.deltaEnergy);
    };
    KingOfTokyoAnimationManager.prototype.resolveSmashDice = function (args) {
        this.addDiceAnimation(6, args.smashedPlayersIds, args.number);
    };
    return KingOfTokyoAnimationManager;
}());
var HeartActionSelector = /** @class */ (function () {
    function HeartActionSelector(game, nodeId, args) {
        var _this = this;
        this.game = game;
        this.nodeId = nodeId;
        this.args = args;
        this.selections = [];
        this.createToggleButtons(nodeId, args);
        dojo.place("<div id=\"".concat(nodeId, "-apply-wrapper\" class=\"action-selector-apply-wrapper\"><button class=\"bgabutton bgabutton_blue action-selector-apply\" id=\"").concat(nodeId, "-apply\">").concat(_('Apply'), "</button></div>"), nodeId);
        document.getElementById("".concat(nodeId, "-apply")).addEventListener('click', function () { return _this.game.applyHeartActions(_this.selections); });
    }
    HeartActionSelector.prototype.createToggleButtons = function (nodeId, args) {
        var _this = this;
        args.dice.filter(function (die) { return die.value === 4; }).forEach(function (die, index) {
            var html = "<div class=\"row\">\n                <div class=\"legend\">\n                    <div class=\"dice-icon dice4\"></div>\n                </div>\n                <div id=\"".concat(nodeId, "-die").concat(index, "\" class=\"toggle-buttons\"></div>\n            </div>");
            dojo.place(html, nodeId);
            _this.createToggleButton("".concat(nodeId, "-die").concat(index), "".concat(nodeId, "-die").concat(index, "-heal"), _('Heal'), function () { return _this.healSelected(index); }, false, true);
            if (!args.canHealWithDice) {
                var buttonDiv = document.getElementById("".concat(nodeId, "-die").concat(index, "-heal"));
                buttonDiv.style.position = 'relative';
                buttonDiv.innerHTML += "<div class=\"icon forbidden\"></div>";
            }
            _this.selections[index] = { action: 'heal' };
            if (args.shrinkRayTokens > 0) {
                _this.createToggleButton("".concat(nodeId, "-die").concat(index), "".concat(nodeId, "-die").concat(index, "-shrink-ray"), _('Remove Shrink Ray token'), function () { return _this.shrinkRaySelected(index); }, !args.canHealWithDice);
                if (!args.canHealWithDice) {
                    var buttonDiv = document.getElementById("".concat(nodeId, "-die").concat(index, "-shrink-ray"));
                    buttonDiv.style.position = 'relative';
                    buttonDiv.innerHTML += "<div class=\"icon forbidden\"></div>";
                }
            }
            if (args.poisonTokens > 0) {
                _this.createToggleButton("".concat(nodeId, "-die").concat(index), "".concat(nodeId, "-die").concat(index, "-poison"), _('Remove Poison token'), function () { return _this.poisonSelected(index); }, !args.canHealWithDice);
                if (!args.canHealWithDice) {
                    var buttonDiv = document.getElementById("".concat(nodeId, "-die").concat(index, "-poison"));
                    buttonDiv.style.position = 'relative';
                    buttonDiv.innerHTML += "<div class=\"icon forbidden\"></div>";
                }
            }
            if (args.hasHealingRay) {
                args.healablePlayers.forEach(function (healablePlayer) {
                    return _this.createToggleButton("".concat(nodeId, "-die").concat(index), "".concat(nodeId, "-die").concat(index, "-heal-player-").concat(healablePlayer.id), dojo.string.substitute(_('Heal player ${player_name}'), { 'player_name': "<span style=\"color: #".concat(healablePlayer.color, "\">").concat(healablePlayer.name, "</span>") }), function () { return _this.healPlayerSelected(index, healablePlayer.id); }, false);
                });
            }
        });
    };
    HeartActionSelector.prototype.createToggleButton = function (destinationId, id, text, callback, disabled, selected) {
        if (selected === void 0) { selected = false; }
        var html = "<div class=\"toggle-button\" id=\"".concat(id, "\">\n            ").concat(text, "\n        </button>");
        dojo.place(html, destinationId);
        if (disabled) {
            dojo.addClass(id, 'disabled');
        }
        else if (selected) {
            dojo.addClass(id, 'selected');
        }
        document.getElementById(id).addEventListener('click', function () { return callback(); });
    };
    HeartActionSelector.prototype.removeOldSelection = function (index) {
        var oldSelectionId = this.selections[index].action == 'heal-player' ? "".concat(this.nodeId, "-die").concat(index, "-heal-player-").concat(this.selections[index].playerId) : "".concat(this.nodeId, "-die").concat(index, "-").concat(this.selections[index].action);
        dojo.removeClass(oldSelectionId, 'selected');
    };
    HeartActionSelector.prototype.healSelected = function (index) {
        if (this.selections[index].action == 'heal') {
            return;
        }
        this.removeOldSelection(index);
        this.selections[index].action = 'heal';
        dojo.addClass("".concat(this.nodeId, "-die").concat(index, "-").concat(this.selections[index].action), 'selected');
        this.checkDisabled();
    };
    HeartActionSelector.prototype.shrinkRaySelected = function (index) {
        if (this.selections[index].action == 'shrink-ray') {
            return;
        }
        this.removeOldSelection(index);
        this.selections[index].action = 'shrink-ray';
        dojo.addClass("".concat(this.nodeId, "-die").concat(index, "-").concat(this.selections[index].action), 'selected');
        this.checkDisabled();
    };
    HeartActionSelector.prototype.poisonSelected = function (index) {
        if (this.selections[index].action == 'poison') {
            return;
        }
        this.removeOldSelection(index);
        this.selections[index].action = 'poison';
        dojo.addClass("".concat(this.nodeId, "-die").concat(index, "-").concat(this.selections[index].action), 'selected');
        this.checkDisabled();
    };
    HeartActionSelector.prototype.healPlayerSelected = function (index, playerId) {
        if (this.selections[index].action == 'heal-player' && this.selections[index].playerId == playerId) {
            return;
        }
        this.removeOldSelection(index);
        this.selections[index].action = 'heal-player';
        this.selections[index].playerId = playerId;
        dojo.addClass("".concat(this.nodeId, "-die").concat(index, "-heal-player-").concat(playerId), 'selected');
        this.checkDisabled();
    };
    HeartActionSelector.prototype.checkDisabled = function () {
        var _this = this;
        var removedShrinkRays = this.selections.filter(function (selection) { return selection.action === 'shrink-ray'; }).length;
        var removedPoisons = this.selections.filter(function (selection) { return selection.action === 'poison'; }).length;
        var healedPlayers = [];
        this.args.healablePlayers.forEach(function (player) { return healedPlayers[player.id] = _this.selections.filter(function (selection) { return selection.action === 'heal-player' && selection.playerId == player.id; }).length; });
        this.selections.forEach(function (selection, index) {
            if (_this.args.shrinkRayTokens > 0) {
                dojo.toggleClass("".concat(_this.nodeId, "-die").concat(index, "-shrink-ray"), 'disabled', selection.action != 'shrink-ray' && removedShrinkRays >= _this.args.shrinkRayTokens);
            }
            if (_this.args.poisonTokens > 0) {
                dojo.toggleClass("".concat(_this.nodeId, "-die").concat(index, "-poison"), 'disabled', selection.action != 'poison' && removedPoisons >= _this.args.poisonTokens);
            }
            if (_this.args.hasHealingRay) {
                _this.args.healablePlayers.forEach(function (player) { return dojo.toggleClass("".concat(_this.nodeId, "-die").concat(index, "-heal-player-").concat(player.id), 'disabled', selection.action != 'heal-player' && selection.playerId != player.id && healedPlayers[player.id] >= player.missingHearts); });
            }
        });
    };
    return HeartActionSelector;
}());
var SmashActionSelector = /** @class */ (function () {
    function SmashActionSelector(game, nodeId, args) {
        var _this = this;
        this.game = game;
        this.nodeId = nodeId;
        this.args = args;
        this.selections = {};
        this.createToggleButtons(nodeId, args);
        dojo.place("<div id=\"".concat(nodeId, "-apply-wrapper\" class=\"action-selector-apply-wrapper\"><button class=\"bgabutton bgabutton_blue action-selector-apply\" id=\"").concat(nodeId, "-apply\">").concat(_('Apply'), "</button></div>"), nodeId);
        document.getElementById("".concat(nodeId, "-apply")).addEventListener('click', function () { return _this.game.applySmashActions(_this.selections); });
    }
    SmashActionSelector.prototype.createToggleButtons = function (nodeId, args) {
        var _this = this;
        args.willBeWoundedIds.forEach(function (playerId) {
            var player = _this.game.getPlayer(playerId);
            var html = "<div class=\"row\">\n                <div class=\"legend\" style=\"color: #".concat(player.color, "\">\n                    ").concat(player.name, "\n                </div>\n                <div id=\"").concat(nodeId, "-player").concat(playerId, "\" class=\"toggle-buttons\"></div>\n            </div>");
            dojo.place(html, nodeId);
            _this.selections[playerId] = 'smash';
            _this.createToggleButton("".concat(nodeId, "-player").concat(playerId), "".concat(nodeId, "-player").concat(playerId, "-smash"), _("Don't steal"), function () { return _this.setSelectedAction(playerId, 'smash'); }, true);
            _this.createToggleButton("".concat(nodeId, "-player").concat(playerId), "".concat(nodeId, "-player").concat(playerId, "-steal"), formatTextIcons(_('Steal 1[Star] and 1[Energy]')), function () { return _this.setSelectedAction(playerId, 'steal'); });
        });
    };
    SmashActionSelector.prototype.createToggleButton = function (destinationId, id, text, callback, selected) {
        if (selected === void 0) { selected = false; }
        var html = "<div class=\"toggle-button\" id=\"".concat(id, "\">\n            ").concat(text, "\n        </button>");
        dojo.place(html, destinationId);
        if (selected) {
            dojo.addClass(id, 'selected');
        }
        document.getElementById(id).addEventListener('click', function () { return callback(); });
    };
    SmashActionSelector.prototype.removeOldSelection = function (playerId) {
        var oldSelectionId = "".concat(this.nodeId, "-player").concat(playerId, "-").concat(this.selections[playerId]);
        dojo.removeClass(oldSelectionId, 'selected');
    };
    SmashActionSelector.prototype.setSelectedAction = function (playerId, action) {
        if (this.selections[playerId] == action) {
            return;
        }
        this.removeOldSelection(playerId);
        this.selections[playerId] = action;
        dojo.addClass("".concat(this.nodeId, "-player").concat(playerId, "-").concat(action), 'selected');
    };
    return SmashActionSelector;
}());
var BACKGROUND_FILENAME = {
    1: 'base.jpg',
    2: 'halloween.jpg',
    3: 'christmas.jpg',
    4: 'powerup.jpg',
    5: 'dark.jpg',
    6: 'base.jpg', // no special background for Origins
};
var PreferencesManager = /** @class */ (function () {
    function PreferencesManager(game) {
        this.game = game;
        this.setupPreferences();
    }
    PreferencesManager.prototype.setupPreferences = function () {
        try {
            document.getElementById('preference_control_203').closest(".preference_choice").style.display = 'none';
            document.getElementById('preference_fontrol_203').closest(".preference_choice").style.display = 'none';
        }
        catch (e) { }
    };
    PreferencesManager.prototype.getGameVersionNumber = function (versionNumber) {
        if (versionNumber > 0) {
            return versionNumber;
        }
        else {
            if (this.game.isOrigins()) {
                return 6;
            }
            else if (this.game.isDarkEdition()) {
                return 5;
            }
            else if (this.game.isPowerUpExpansion()) {
                return 4;
            }
            else if (this.game.isHalloweenExpansion()) {
                return 2;
            }
            return 1;
        }
    };
    PreferencesManager.prototype.getBackgroundFilename = function () {
        var prefId = this.getGameVersionNumber(this.game.getGameUserPreference(205));
        return BACKGROUND_FILENAME[prefId];
    };
    PreferencesManager.prototype.onPreferenceChange = function (prefId, prefValue) {
        switch (prefId) {
            case 201:
                this.game.setFont(prefValue);
                break;
            case 203:
                if (prefValue == 2) {
                    dojo.destroy('board-corner-highlight');
                    dojo.destroy('twoPlayersVariant-message');
                }
                break;
            case 204:
                document.getElementsByTagName('html')[0].dataset.background = '' + this.getGameVersionNumber(prefValue);
                break;
            case 205:
                document.getElementsByTagName('html')[0].dataset.dice = '' + this.getGameVersionNumber(prefValue);
                break;
        }
    };
    PreferencesManager.prototype.getDiceScoringColor = function () {
        var prefId = this.getGameVersionNumber(this.game.getGameUserPreference(205));
        switch (prefId) {
            case 2: return '000000';
            case 3: return '0096CC';
            case 4: return '157597';
            case 5: return 'ecda5f';
            case 6: return '129447';
        }
        return '96c93c';
    };
    return PreferencesManager;
}());
var WICKEDNESS_MONSTER_ICON_POSITION = [
    [2, 270],
    [32, 317],
    [84, 312],
    [124, 280],
    [103, 235],
    [82, 191],
    [124, 164],
    [83, 130],
    [41, 96],
    [84, 58],
    [124, 33],
];
var WICKEDNESS_MONSTER_ICON_POSITION_DARK_EDITION = [
    [-28, 324],
    [24, 410],
    [-2, 370],
    [39, 328],
    [22, 284],
    [-5, 236],
    [38, 197],
    [1, 156],
    [32, 107],
    [1, 70],
    [37, 29],
];
var TableCenter = /** @class */ (function () {
    function TableCenter(game, players, boardImgUrl, visibleCards, topDeckCard, deckCardsCount, wickednessTiles, tokyoTowerLevels, curseCard, hiddenCurseCardCount, visibleCurseCardCount, topCurseDeckCard) {
        var _this = this;
        this.game = game;
        this.wickednessPoints = new Map();
        document.getElementById("board").style.backgroundImage = "url(".concat(g_gamethemeurl, "img/").concat(boardImgUrl, ")");
        this.createVisibleCards(visibleCards, topDeckCard, deckCardsCount);
        if (game.isWickednessExpansion()) {
            dojo.place("\n            <div id=\"wickedness-board-wrapper\">\n                <div id=\"wickedness-board\"></div>\n            </div>", 'full-board');
            this.createWickednessTiles(wickednessTiles);
            if (!game.isDarkEdition()) {
                document.getElementById("table-cards").dataset.wickednessBoard = 'true';
            }
            players.forEach(function (player) {
                dojo.place("<div id=\"monster-icon-".concat(player.id, "-wickedness\" class=\"monster-icon monster").concat(player.monster, "\" style=\"background-color: ").concat(player.monster > 100 ? 'unset' : '#' + player.color, ";\"></div>"), 'wickedness-board');
                _this.wickednessPoints.set(Number(player.id), Number(player.wickedness));
            });
            this.moveWickednessPoints();
        }
        if (game.isKingkongExpansion()) {
            dojo.place("<div id=\"tokyo-tower-0\" class=\"tokyo-tower-wrapper\"></div>", 'full-board');
            this.tokyoTower = new TokyoTower('tokyo-tower-0', tokyoTowerLevels);
        }
        if (game.isAnubisExpansion()) {
            this.createCurseCard(curseCard, hiddenCurseCardCount, visibleCurseCardCount, topCurseDeckCard);
        }
        else {
            document.getElementById('table-curse-cards').style.display = 'none';
        }
    }
    TableCenter.prototype.createVisibleCards = function (visibleCards, topDeckCard, deckCardsCount) {
        var _this = this;
        this.deck = new Deck(this.game.cardsManager, document.getElementById('deck'), {
            cardNumber: deckCardsCount,
            topCard: topDeckCard,
            shadowDirection: 'top-right',
        });
        this.visibleCards = new SlotStock(this.game.cardsManager, document.getElementById('visible-cards'), {
            slotsIds: [1, 2, 3],
            mapCardToSlot: function (card) { return card.location_arg; },
        });
        this.visibleCards.onCardClick = function (card) { return _this.game.onVisibleCardClick(_this.visibleCards, card); };
        this.setVisibleCards(visibleCards, true);
    };
    TableCenter.prototype.createCurseCard = function (curseCard, hiddenCurseCardCount, visibleCurseCardCount, topCurseDeckCard) {
        dojo.place("<div id=\"curse-wrapper\">\n            <div id=\"curse-deck\"></div>\n            <div id=\"curse-card\"></div>\n        </div>", 'table-curse-cards');
        this.curseCard = new Deck(this.game.curseCardsManager, document.getElementById('curse-card'), {
            cardNumber: visibleCurseCardCount,
            topCard: curseCard,
        });
        this.curseDeck = new Deck(this.game.curseCardsManager, document.getElementById('curse-deck'), {
            cardNumber: hiddenCurseCardCount,
            topCard: topCurseDeckCard,
        });
        this.game.addTooltipHtml("curse-deck", "\n        <strong>".concat(_("Curse card pile."), "</strong>\n        <div> ").concat(dojo.string.substitute(_("Discard the current Curse and reveal the next one by rolling ${changeCurseCard}."), { 'changeCurseCard': '<div class="anubis-icon anubis-icon1"></div>' }), "</div>\n        "));
    };
    TableCenter.prototype.setVisibleCardsSelectionMode = function (mode) {
        this.visibleCards.setSelectionMode(mode);
    };
    TableCenter.prototype.setVisibleCardsSelectionClass = function (visible) {
        document.getElementById('table-center').classList.toggle('double-selection', visible);
    };
    TableCenter.prototype.showPickStock = function (cards) {
        var _this = this;
        if (!this.pickCard) {
            dojo.place('<div id="pick-stock" class="card-stock"></div>', 'deck-wrapper');
            this.pickCard = new LineStock(this.game.cardsManager, document.getElementById('pick-stock'));
            this.pickCard.setSelectionMode('single');
            this.pickCard.onCardClick = function (card) { return _this.game.onVisibleCardClick(_this.pickCard, card); };
        }
        else {
            document.getElementById('pick-stock').style.display = null;
        }
        this.pickCard.addCards(cards);
    };
    TableCenter.prototype.hidePickStock = function () {
        var div = document.getElementById('pick-stock');
        if (div) {
            document.getElementById('pick-stock').style.display = 'none';
            this.pickCard.removeAll();
        }
    };
    TableCenter.prototype.renewCards = function (cards, topDeckCard, deckCount) {
        this.visibleCards.removeAll();
        var promise = this.setVisibleCards(cards, false, deckCount, topDeckCard);
        return promise;
    };
    TableCenter.prototype.setTopDeckCard = function (topDeckCard, deckCount) {
        this.deck.setCardNumber(deckCount, topDeckCard);
    };
    TableCenter.prototype.setInitialCards = function (cards) {
        this.deck.addCards(cards, undefined, { visible: false });
        this.visibleCards.removeAll();
        this.visibleCards.setSlotsIds([0, 1]);
        var cardsWithSlot = cards.map(function (card, index) { return (__assign(__assign({}, card), { location_arg: index })); });
        return this.visibleCards.addCards(cardsWithSlot, { fromStock: this.deck, rotationDelta: 90 }, undefined, true);
    };
    TableCenter.prototype.setVisibleCards = function (cards, init, deckCount, topDeckCard) {
        var _this = this;
        if (deckCount === void 0) { deckCount = null; }
        if (topDeckCard === void 0) { topDeckCard = null; }
        if (init) {
            return this.visibleCards.addCards(cards);
        }
        else {
            this.setTopDeckCard(topDeckCard, deckCount);
            var cardsForDeck = cards.slice();
            cardsForDeck.sort(function (a, b) { return b.location_arg - a.location_arg; });
            // add 3 - 2 - 1
            this.deck.addCards(cardsForDeck, undefined, { visible: false, autoUpdateCardNumber: false, autoRemovePreviousCards: false });
            // reveal 1 - 2 - 3
            this.visibleCards.setSlotsIds([1, 2, 3]);
            return this.visibleCards.addCards(cards, { fromStock: this.deck, rotationDelta: 90 }, undefined, true).then(function () {
                return _this.setTopDeckCard(topDeckCard, deckCount);
            });
        }
    };
    TableCenter.prototype.removeOtherCardsFromPick = function (cardId) {
        var _this = this;
        var _a;
        var removeFromPickIds = (_a = this.pickCard) === null || _a === void 0 ? void 0 : _a.getCards().map(function (item) { return Number(item.id); });
        removeFromPickIds === null || removeFromPickIds === void 0 ? void 0 : removeFromPickIds.forEach(function (id) {
            if (id !== cardId) {
                _this.pickCard.removeCard({ id: id });
            }
        });
    };
    TableCenter.prototype.getVisibleCards = function () {
        return this.visibleCards;
    };
    TableCenter.prototype.getDeck = function () {
        return this.deck;
    };
    TableCenter.prototype.getPickCard = function () {
        return this.pickCard;
    };
    TableCenter.prototype.getTokyoTower = function () {
        return this.tokyoTower;
    };
    TableCenter.prototype.changeCurseCard = function (card, hiddenCurseCardCount, topCurseDeckCard) {
        var promise = this.curseCard.addCard(card, { fromStock: this.curseDeck, originalSide: 'back' });
        this.curseDeck.setCardNumber(hiddenCurseCardCount, topCurseDeckCard);
        return promise;
    };
    TableCenter.prototype.createWickednessTiles = function (wickednessTiles) {
        var _this = this;
        this.wickednessDecks = new WickednessDecks(this.game.wickednessTilesManager);
        this.wickednessDecks.onTileClick = function (card) {
            var args = _this.game.gamedatas.gamestate.args;
            if (args.noExtraTurnWarning.includes(card.type)) {
                _this.game.confirmationDialog(_this.game.getNoExtraTurnWarningMessage(), function () { return _this.game.takeWickednessTile(card.id); });
            }
            else {
                _this.game.takeWickednessTile(card.id);
            }
        };
        this.wickednessDecks.addCards(wickednessTiles);
    };
    TableCenter.prototype.moveWickednessPoints = function () {
        var _this = this;
        this.wickednessPoints.forEach(function (wickedness, playerId) {
            var markerDiv = document.getElementById("monster-icon-".concat(playerId, "-wickedness"));
            markerDiv.dataset.wickedness = '' + wickedness;
            var positionArray = _this.game.isDarkEdition() ? WICKEDNESS_MONSTER_ICON_POSITION_DARK_EDITION : WICKEDNESS_MONSTER_ICON_POSITION;
            var position = positionArray[wickedness];
            var topShift = 0;
            var leftShift = 0;
            _this.wickednessPoints.forEach(function (iWickedness, iPlayerId) {
                if (iWickedness === wickedness && iPlayerId < playerId) {
                    topShift += 5;
                    leftShift += 5;
                }
            });
            markerDiv.style.left = "".concat(position[0] + leftShift, "px");
            markerDiv.style.top = "".concat(position[1] + topShift, "px");
        });
    };
    TableCenter.prototype.setWickedness = function (playerId, wickedness) {
        this.wickednessPoints.set(playerId, wickedness);
        this.moveWickednessPoints();
    };
    TableCenter.prototype.showWickednessTiles = function (level) {
        var _this = this;
        WICKEDNESS_LEVELS.filter(function (l) { return l !== level; }).forEach(function (l) { return _this.wickednessDecks.setOpened(l, false); });
        if (level !== null) {
            this.wickednessDecks.setOpened(level, true);
        }
    };
    TableCenter.prototype.setWickednessTilesSelectable = function (level, show, selectable) {
        this.showWickednessTiles(show ? level : null);
        this.wickednessDecks.setSelectableLevel(selectable ? level : null);
    };
    TableCenter.prototype.removeWickednessTileFromPile = function (level, removedTile) {
        this.wickednessDecks.removeCard(removedTile);
        this.wickednessDecks.setOpened(level, false);
        this.wickednessDecks.setSelectableLevel(null);
    };
    return TableCenter;
}());
var RULEBOOK_LINKS = [
    {
        'en': 'https://cdn.shopify.com/s/files/1/0049/3351/7425/files/KOT2-rulebook_EN.pdf?1387',
        'fr': 'https://iello.fr/regles/regles_KOTv2.pdf',
    },
    {
        'en': 'https://www.fgbradleys.com/rules/rules6/King%20of%20Tokyo%20Halloween%20-%20rules.pdf',
        'fr': 'https://www.iello.fr/regles/KOT_HALLOWEEN_regles.pdf',
    },
    {
        'en': 'https://cdn.1j1ju.com/medias/47/0e/7f-king-of-tokyo-new-york-monster-pack-cthulhu-rulebook.pdf',
        'fr': 'https://www.play-in.com/pdf/rules_games/monster_pack_cthulhu_-_extension_king_of_tokyo_regles_fr.pdf',
    },
    {
        'en': 'https://www.iello.fr/regles/KOT_KingKong-US-Rules.pdf',
        'fr': 'http://iello.fr/regles/KOT_KONG_regles.pdf',
    },
    {
        'en': 'http://iello.fr/regles/KOT-Anubis-rulebook-EN.pdf',
        'fr': 'http://iello.fr/regles/51530_regles.pdf',
    },
    {
        'en': 'https://cdn.1j1ju.com/medias/6f/b6/07-king-of-tokyo-new-york-monster-pack-cybertooth-rulebook.pdf',
        'fr': 'https://cdn.1j1ju.com/medias/80/e7/99-king-of-tokyo-new-york-monster-pack-cybertooth-regle.pdf',
    },
    {
        'en': 'https://boardgamegeek.com/filepage/241513/english-rulebook',
        'fr': 'https://iello.fr/regles/KOT_mechancete_Rules_FR.pdf',
    },
    {
        'en': 'https://cdn.1j1ju.com/medias/69/8c/32-king-of-tokyo-power-up-rulebook.pdf',
        'fr': 'https://cdn.1j1ju.com/medias/8c/62/83-king-of-tokyo-power-up-regle.pdf',
    },
    {
        'en': 'https://cdn.1j1ju.com/medias/53/d4/2e-king-of-tokyo-dark-edition-rulebook.pdf',
        'fr': 'http://iello.fr/regles/KOT%20DARK_rulebook.pdf',
    },
];
var EXPANSION_NUMBER = 8;
var ActivatedExpansionsPopin = /** @class */ (function () {
    function ActivatedExpansionsPopin(gamedatas, language) {
        if (language === void 0) { language = 'en'; }
        var _this = this;
        this.gamedatas = gamedatas;
        this.language = language;
        this.activatedExpansions = [];
        if (this.gamedatas.halloweenExpansion) {
            this.activatedExpansions.push(1);
        }
        if (this.gamedatas.cthulhuExpansion) {
            this.activatedExpansions.push(2);
        }
        if (this.gamedatas.kingkongExpansion) {
            this.activatedExpansions.push(3);
        }
        if (this.gamedatas.anubisExpansion) {
            this.activatedExpansions.push(4);
        }
        if (this.gamedatas.cybertoothExpansion) {
            this.activatedExpansions.push(5);
        }
        if (this.gamedatas.wickednessExpansion) {
            this.activatedExpansions.push(6);
        }
        if (this.gamedatas.powerUpExpansion) {
            this.activatedExpansions.push(7);
        }
        if (this.gamedatas.darkEdition) {
            this.activatedExpansions.push(8);
        }
        if (this.gamedatas.mindbugExpansion) {
            // TODOMB this.activatedExpansions.push(9);
        }
        if (this.activatedExpansions.length) {
            var html = "\n            <div>\t\t\t\t\t\n                <button id=\"active-expansions-button\" class=\"bgabutton bgabutton_gray\">\n                    <div class=\"title\">".concat(_('Active expansions'), "</div>\n                    <div class=\"expansion-zone-list\">");
            for (var i = 1; i <= EXPANSION_NUMBER; i++) {
                var activated = this.activatedExpansions.includes(i);
                html += "<div class=\"expansion-zone\" data-expansion=\"".concat(i, "\" data-activated=\"").concat(activated.toString(), "\"><div class=\"expansion-icon\"></div></div>");
            }
            html += "        </div>\n                </button>\n            </div>";
            dojo.place(html, "player_boards");
            document.getElementById("active-expansions-button").addEventListener("click", function () { return _this.createPopin(); });
        }
    }
    ActivatedExpansionsPopin.prototype.getTitle = function (index) {
        switch (index) {
            case 0: return _('Base game');
            case 1: return _('“Halloween” event (Costume cards)');
            case 2: return _('“Battle of the Gods, part I” event (Cultists)');
            case 3: return _('“Nature vs. Machine, part I” event (Tokyo Tower)');
            case 4: return _('“Battle of the Gods: the Revenge!” event (Curse cards)');
            case 5: return _('“Nature vs. Machine: the Comeback!” event (Berserk)');
            case 6: return _('“Even more wicked!” event');
            case 7: return _('Power-Up! (Evolutions)');
            case 8: return _('Dark Edition');
        }
    };
    ActivatedExpansionsPopin.prototype.getDescription = function (index) {
        switch (index) {
            case 1: return formatTextIcons(_('Halloween expansion brings a new set of Costume cards. Each player start with a Costume card (chosen between 2). When you smash a player with at least 3 [diceSmash], you can steal their Costumes cards (by paying its cost).'));
            case 2: return formatTextIcons("<p>".concat(_("After resolving your dice, if you rolled four identical faces, take a Cultist tile"), "</p>\n            <p>").concat(_("At any time, you can discard one of your Cultist tiles to gain either: 1[Heart], 1[Energy], or one extra Roll."), "</p>"));
            case 3: return formatTextIcons("<p>".concat(_("Claim a tower level by rolling at least [dice1][dice1][dice1][dice1] while in Tokyo."), "</p>\n            <p>").concat(_("<strong>Monsters who control one or more levels</strong> gain the bonuses at the beginning of their turn: 1[Heart] for the bottom level, 1[Heart] and 1[Energy] for the middle level (the bonuses are cumulative)."), "</p>\n            <p><strong>").concat(_("Claiming the top level automatically wins the game."), "</strong></p>"));
            case 4: return formatTextIcons(_("Anubis brings the Curse cards and the Die of Fate. The Curse card on the table show a permanent effect, applied to all players, and the Die of Fate can trigger the Ankh effect or the Snake effect."));
            case 5: return formatTextIcons("<p>".concat(_("When you roll 4 or more [diceSmash], you are in Berserk mode!"), "</p>\n            <p>").concat(_("You play with the additional Berserk die, until you heal yourself."), "</p>"));
            case 6: return formatTextIcons(_("When you roll 3 or more [dice1] or [dice2], gain Wickeness points to get special Tiles."));
            case 7: return formatTextIcons(_("Power-Up! expansion brings new sets of Evolution cards, giving each Monster special abilities. Each player start with an Evolution card (chosen between 2). You can play this Evolution card any time. When you roll 3 or more [diceHeart], you can choose a new Evolution card."));
            case 8: return _("Dark Edition brings gorgeous art, and the wickedness track is included in the game, with a new set of cards.");
        }
        return '';
    };
    ActivatedExpansionsPopin.prototype.viewRulebook = function (index) {
        var _a;
        var rulebookContainer = document.getElementById("rulebook-".concat(index));
        var show = rulebookContainer.innerHTML === '';
        if (show) {
            var url = (_a = RULEBOOK_LINKS[index][this.language]) !== null && _a !== void 0 ? _a : RULEBOOK_LINKS[index]['en'];
            var html = "<iframe src=\"".concat(url, "\" style=\"width: 100%; height: 60vh\"></iframe>");
            rulebookContainer.innerHTML = html;
        }
        else {
            rulebookContainer.innerHTML = '';
        }
        document.getElementById("show-rulebook-".concat(index)).innerHTML = show ? _('Hide rulebook') : _('Show rulebook');
    };
    ActivatedExpansionsPopin.prototype.createBlock = function (index) {
        var _this = this;
        var _a;
        var url = (_a = RULEBOOK_LINKS[index][this.language]) !== null && _a !== void 0 ? _a : RULEBOOK_LINKS[index]['en'];
        var activated = this.activatedExpansions.includes(index);
        var html = "\n        <details data-expansion=\"".concat(index, "\" data-activated=\"").concat(activated.toString(), "\">\n            <summary><span class=\"activation-status\">").concat(activated ? _('Enabled') : _('Disabled'), "</span>").concat(this.getTitle(index), "</summary>\n            <div class=\"description\">").concat(this.getDescription(index), "</div>\n            <p class=\"block-buttons\">\n                <button id=\"show-rulebook-").concat(index, "\" class=\"bgabutton bgabutton_blue\">").concat(_('Show rulebook'), "</button>\n                <a href=\"").concat(url, "\" target=\"_blank\" class=\"bgabutton bgabutton_blue\">").concat(_('Open rulebook in a new tab'), "</a>\n            </p>\n            <div id=\"rulebook-").concat(index, "\"></div>\n        </details>");
        dojo.place(html, "playermat-container-modal");
        document.getElementById("show-rulebook-".concat(index)).addEventListener("click", function () { return _this.viewRulebook(index); });
    };
    ActivatedExpansionsPopin.prototype.createPopin = function () {
        var _this = this;
        var html = "\n        <div id=\"popin_showActivatedExpansions_container\" class=\"kingoftokyo_popin_container\">\n            <div id=\"popin_showActivatedExpansions_underlay\" class=\"kingoftokyo_popin_underlay\"></div>\n                <div id=\"popin_showActivatedExpansions_wrapper\" class=\"kingoftokyo_popin_wrapper\">\n                <div id=\"popin_showActivatedExpansions\" class=\"kingoftokyo_popin\">\n                    <a id=\"popin_showActivatedExpansions_close\" class=\"closeicon\"><i class=\"fa fa-times fa-2x\" aria-hidden=\"true\"></i></a>\n                                \n                    <h2>".concat(_('Active expansions'), "</h2>\n                    <div id=\"playermat-container-modal\"></div>\n                </div>\n            </div>\n        </div>");
        dojo.place(html, $(document.body));
        document.getElementById("popin_showActivatedExpansions_close").addEventListener("click", function () { return _this.closePopin(); });
        document.getElementById("popin_showActivatedExpansions_underlay").addEventListener("click", function () { return _this.closePopin(); });
        for (var i = 0; i <= EXPANSION_NUMBER; i++) {
            html += this.createBlock(i);
        }
    };
    ActivatedExpansionsPopin.prototype.closePopin = function () {
        document.getElementById('popin_showActivatedExpansions_container').remove();
    };
    return ActivatedExpansionsPopin;
}());
var MonsterGroup = /** @class */ (function () {
    function MonsterGroup(monsters, title, color) {
        this.monsters = monsters;
        this.title = title;
        this.color = color;
    }
    return MonsterGroup;
}());
var MonsterSelector = /** @class */ (function () {
    function MonsterSelector(game) {
        this.game = game;
        this.BONUS_GROUP = new MonsterGroup([], _('Bonus'), '#ffffff');
        this.MONSTER_GROUPS = [
            new MonsterGroup([1, 2, 3, 4, 5, 6, 102, 104, 105, 106, 114, 115], this.game.isDarkEdition() ? 'King of Tokyo Dark Edition' : 'King of Tokyo', '#ffcf13'),
            new MonsterGroup([7, 8], _('Halloween expansion'), '#ff8200'),
            new MonsterGroup([18], _('Monster Box exclusive'), '#dd4271'),
            new MonsterGroup([9, 10, 11, 12], _('Monster Packs'), '#a9e9ae'),
            new MonsterGroup([13], _('Power-Up! expansion'), '#5d7b38'),
            new MonsterGroup([21, 22, 23, 24, 25, 26], 'King of New-York', '#645195'),
            new MonsterGroup([41, 42, 43, 44, 45], 'King of Monster Island', '#e82519'),
            new MonsterGroup([51, 52, 53, 54], _('King of Tokyo Origins'), '#f78d33'),
            new MonsterGroup([61, 62, 63], _('Mindbug expansion'), '#b14e85'),
        ];
    }
    MonsterSelector.prototype.onEnteringPickMonster = function (args) {
        var _this = this;
        // TODO clean only needed
        var html = "";
        var bonusMonsters = args.availableMonsters.filter(function (monster) { return !_this.MONSTER_GROUPS.some(function (monsterGroup) { return monsterGroup.monsters.includes(monster); }); });
        __spreadArray(__spreadArray([], this.MONSTER_GROUPS, true), [this.BONUS_GROUP], false).filter(function (group) {
            var bonus = !group.monsters.length;
            return args.availableMonsters.some(function (monster) { return (bonus ? bonusMonsters : group.monsters).includes(monster); });
        }).forEach(function (group) {
            var bonus = !group.monsters.length;
            html += "\n            <div class=\"monster-group\">\n                <div class=\"title\" style=\"--title-color: ".concat(group.color, ";\">").concat(group.title, "</div>      \n                <div class=\"monster-group-monsters\">");
            var groupMonsters = args.availableMonsters.filter(function (monster) { return (bonus ? bonusMonsters : group.monsters).includes(monster); });
            groupMonsters.forEach(function (monster) {
                html += "\n                    <div id=\"pick-monster-figure-".concat(monster, "-wrapper\">\n                        <div id=\"pick-monster-figure-").concat(monster, "\" class=\"monster-figure monster").concat(monster, "\"></div>");
                if (_this.game.isPowerUpExpansion()) {
                    html += "<div><button id=\"see-monster-evolution-".concat(monster, "\" class=\"bgabutton bgabutton_blue see-evolutions-button\"><div class=\"player-evolution-card\"></div>").concat(_('Show Evolutions'), "</button></div>");
                }
                html += "</div>";
            });
            html += "    </div>      \n            </div>\n            ";
        });
        document.getElementById('monster-pick').innerHTML = html;
        args.availableMonsters.forEach(function (monster) {
            document.getElementById("pick-monster-figure-".concat(monster)).addEventListener('click', function () { return _this.game.pickMonster(monster); });
            if (_this.game.isPowerUpExpansion()) {
                document.getElementById("see-monster-evolution-".concat(monster)).addEventListener('click', function () { return _this.showMonsterEvolutions(monster % 100); });
            }
        });
        var isCurrentPlayerActive = this.game.isCurrentPlayerActive();
        dojo.toggleClass('monster-pick', 'selectable', isCurrentPlayerActive);
    };
    MonsterSelector.prototype.showMonsterEvolutions = function (monster) {
        var cardsTypes = [];
        for (var i = 1; i <= 8; i++) {
            cardsTypes.push(monster * 10 + i);
        }
        this.game.showEvolutionsPopin(cardsTypes, _("Monster Evolution cards"));
    };
    return MonsterSelector;
}());
var ANIMATION_MS = 1500;
var PUNCH_SOUND_DURATION = 250;
var ACTION_TIMER_DURATION = 5;
var SYMBOL_AS_STRING_PADDED = ['[Star]', null, null, null, '[Heart]', '[Energy]'];
// @ts-ignore
GameGui = (function () {
    function GameGui() { }
    return GameGui;
})();
var KingOfTokyo = /** @class */ (function (_super) {
    __extends(KingOfTokyo, _super);
    function KingOfTokyo() {
        var _this = _super.call(this) || this;
        _this.healthCounters = [];
        _this.energyCounters = [];
        _this.wickednessCounters = [];
        _this.cultistCounters = [];
        _this.handCounters = [];
        _this.playerTables = [];
        //private rapidHealingSyncHearts: number;
        _this.towerLevelsOwners = [];
        _this.falseBlessingAnkhAction = null;
        _this.cardLogId = 0;
        return _this;
    }
    /*
        setup:

        This method must set up the game user interface according to current game situation specified
        in parameters.

        The method is called each time the game interface is displayed to a player, ie:
        _ when the game starts
        _ when a player refreshes the game page (F5)

        "gamedatas" argument contains all datas retrieved by your "getAllDatas" PHP method.
    */
    KingOfTokyo.prototype.setup = function (gamedatas) {
        var _this = this;
        if (gamedatas.origins) {
            document.getElementsByTagName('html')[0].dataset.origins = 'true';
        }
        else if (gamedatas.darkEdition) {
            document.getElementsByTagName('html')[0].dataset.darkEdition = 'true';
        }
        // needd to preload background
        this.preferencesManager = new PreferencesManager(this);
        var players = Object.values(gamedatas.players);
        // ignore loading of some pictures
        this.dontPreloadImage("animations-halloween.jpg");
        this.dontPreloadImage("animations-christmas.jpg");
        this.dontPreloadImage("christmas_dice.png");
        if (!gamedatas.halloweenExpansion) {
            this.dontPreloadImage("costume-cards.jpg");
            this.dontPreloadImage("orange_dice.png");
        }
        if (!gamedatas.powerUpExpansion) {
            this.dontPreloadImage("animations-powerup.jpg");
            this.dontPreloadImage("powerup_dice.png");
        }
        // load main board
        var boardDir = gamedatas.origins ? "origins" : (gamedatas.darkEdition ? "dark-edition" : "base");
        var boardFile = gamedatas.twoPlayersVariant ? "2pvariant.jpg" : "standard.jpg";
        var boardImgUrl = "boards/".concat(boardDir, "/").concat(boardFile);
        g_img_preload.push(boardImgUrl);
        g_img_preload.push("backgrounds/".concat(this.preferencesManager.getBackgroundFilename()));
        log("Starting game setup");
        this.gamedatas = gamedatas;
        log('gamedatas', gamedatas);
        if (gamedatas.halloweenExpansion) {
            document.body.classList.add('halloween');
        }
        if (gamedatas.kingkongExpansion) {
            gamedatas.tokyoTowerLevels.forEach(function (level) { return _this.towerLevelsOwners[level] = 0; });
            players.forEach(function (player) { return player.tokyoTowerLevels.forEach(function (level) { return _this.towerLevelsOwners[level] = Number(player.id); }); });
        }
        if (gamedatas.twoPlayersVariant) {
            this.addTwoPlayerVariantNotice(gamedatas);
        }
        this.animationManager = new AnimationManager(this);
        this.cardsManager = new CardsManager(this);
        this.curseCardsManager = new CurseCardsManager(this);
        this.wickednessTilesManager = new WickednessTilesManager(this);
        this.evolutionCardsManager = new EvolutionCardsManager(this);
        this.SHINK_RAY_TOKEN_TOOLTIP = dojo.string.substitute(formatTextIcons(_("Shrink ray tokens (given by ${card_name}). Reduce dice count by one per token. Use you [diceHeart] to remove them.")), { 'card_name': this.cardsManager.getCardName(40, 'text-only') });
        this.POISON_TOKEN_TOOLTIP = dojo.string.substitute(formatTextIcons(_("Poison tokens (given by ${card_name}). Make you lose one [heart] per token at the end of your turn. Use you [diceHeart] to remove them.")), { 'card_name': this.cardsManager.getCardName(35, 'text-only') });
        this.createPlayerPanels(gamedatas);
        setTimeout(function () { var _a, _b; return new ActivatedExpansionsPopin(gamedatas, (_b = (_a = _this.players_metadata) === null || _a === void 0 ? void 0 : _a[_this.getPlayerId()]) === null || _b === void 0 ? void 0 : _b.language); }, 500);
        this.monsterSelector = new MonsterSelector(this);
        this.diceManager = new DiceManager(this);
        this.kotAnimationManager = new KingOfTokyoAnimationManager(this, this.diceManager);
        this.tableCenter = new TableCenter(this, players, boardImgUrl, gamedatas.visibleCards, gamedatas.topDeckCard, gamedatas.deckCardsCount, gamedatas.wickednessTiles, gamedatas.tokyoTowerLevels, gamedatas.curseCard, gamedatas.hiddenCurseCardCount, gamedatas.visibleCurseCardCount, gamedatas.topCurseDeckCard);
        this.createPlayerTables(gamedatas);
        this.tableManager = new TableManager(this, this.playerTables);
        // placement of monster must be after TableManager first paint
        setTimeout(function () { return _this.playerTables.forEach(function (playerTable) { return playerTable.initPlacement(); }); }, 200);
        this.setMimicToken('card', gamedatas.mimickedCards.card);
        this.setMimicToken('tile', gamedatas.mimickedCards.tile);
        this.setMimicEvolutionToken(gamedatas.mimickedCards.evolution);
        var playerId = this.getPlayerId();
        var currentPlayer = players.find(function (player) { return Number(player.id) === playerId; });
        if (currentPlayer === null || currentPlayer === void 0 ? void 0 : currentPlayer.rapidHealing) {
            this.addRapidHealingButton(currentPlayer.energy, currentPlayer.health >= currentPlayer.maxHealth);
        }
        if (currentPlayer === null || currentPlayer === void 0 ? void 0 : currentPlayer.mothershipSupport) {
            this.addMothershipSupportButton(currentPlayer.energy, currentPlayer.health >= currentPlayer.maxHealth);
        }
        if (currentPlayer === null || currentPlayer === void 0 ? void 0 : currentPlayer.cultists) {
            this.addRapidCultistButtons(currentPlayer.health >= currentPlayer.maxHealth);
        }
        if ((currentPlayer === null || currentPlayer === void 0 ? void 0 : currentPlayer.location) > 0) {
            this.addAutoLeaveUnderButton();
        }
        this.setupNotifications();
        document.getElementById('zoom-out').addEventListener('click', function () { var _a; return (_a = _this.tableManager) === null || _a === void 0 ? void 0 : _a.zoomOut(); });
        document.getElementById('zoom-in').addEventListener('click', function () { var _a; return (_a = _this.tableManager) === null || _a === void 0 ? void 0 : _a.zoomIn(); });
        if (gamedatas.kingkongExpansion) {
            var tooltip = formatTextIcons("\n            <h3>".concat(_("Tokyo Tower"), "</h3>\n            <p>").concat(_("Claim a tower level by rolling at least [dice1][dice1][dice1][dice1] while in Tokyo."), "</p>\n            <p>").concat(_("<strong>Monsters who control one or more levels</strong> gain the bonuses at the beginning of their turn: 1[Heart] for the bottom level, 1[Heart] and 1[Energy] for the middle level (the bonuses are cumulative)."), "</p>\n            <p><strong>").concat(_("Claiming the top level automatically wins the game."), "</strong></p>\n            "));
            this.addTooltipHtmlToClass('tokyo-tower-tooltip', tooltip);
        }
        if (gamedatas.cybertoothExpansion) {
            var tooltip = formatTextIcons("\n            <h3>".concat(_("Berserk mode"), "</h3>\n            <p>").concat(_("When you roll 4 or more [diceSmash], you are in Berserk mode!"), "</p>\n            <p>").concat(_("You play with the additional Berserk die, until you heal yourself."), "</p>"));
            this.addTooltipHtmlToClass('berserk-tooltip', tooltip);
        }
        if (gamedatas.cthulhuExpansion) {
            this.CULTIST_TOOLTIP = formatTextIcons("\n            <h3>".concat(_("Cultists"), "</h3>\n            <p>").concat(_("After resolving your dice, if you rolled four identical faces, take a Cultist tile"), "</p>\n            <p>").concat(_("At any time, you can discard one of your Cultist tiles to gain either: 1[Heart], 1[Energy], or one extra Roll."), "</p>"));
            this.addTooltipHtmlToClass('cultist-tooltip', this.CULTIST_TOOLTIP);
        }
        // override to allow icons in messages
        var oldShowMessage = this.showMessage;
        this.showMessage = function (msg, type) { return oldShowMessage(formatTextIcons(msg), type); };
        if (gamedatas.mindbug) {
            this.notif_mindbugPlayer(gamedatas.mindbug);
        }
        log("Ending game setup");
    };
    // @ts-ignore
    KingOfTokyo.prototype.onGameUserPreferenceChanged = function (pref_id, pref_value) {
        this.preferencesManager.onPreferenceChange(pref_id, pref_value);
    };
    ///////////////////////////////////////////////////
    //// Game & client states
    // onEnteringState: this method is called each time we are entering into a new game state.
    //                  You can use this method to perform some user interface changes at this moment.
    //
    KingOfTokyo.prototype.onEnteringState = function (stateName, args) {
        var _a;
        log('Entering state: ' + stateName, args.args);
        this.showActivePlayer(Number(args.active_player));
        var pickMonsterPhase = ['pickMonster', 'PickMonsterNextPlayer'].includes(stateName);
        var pickEvolutionForDeckPhase = ['pickEvolutionForDeck', 'NextPickEvolutionForDeck'].includes(stateName);
        if (!pickMonsterPhase) {
            this.removeMonsterChoice();
        }
        if (!pickMonsterPhase && !pickEvolutionForDeckPhase) {
            this.removeMutantEvolutionChoice();
            this.showMainTable();
        }
        if (this.isPowerUpExpansion()) {
            var evolutionCardsSingleState = this.gamedatas.EVOLUTION_CARDS_SINGLE_STATE[stateName];
            if (evolutionCardsSingleState) {
                (_a = this.getPlayerTable(this.getPlayerId())) === null || _a === void 0 ? void 0 : _a.setEvolutionCardsSingleState(evolutionCardsSingleState, true);
            }
        }
        switch (stateName) {
            case 'pickMonster':
                dojo.addClass('kot-table', 'pickMonsterOrEvolutionDeck');
                this.monsterSelector.onEnteringPickMonster(args.args);
                break;
            case 'pickEvolutionForDeck':
                dojo.addClass('kot-table', 'pickMonsterOrEvolutionDeck');
                this.onEnteringPickEvolutionForDeck(args.args);
                break;
            case 'chooseInitialCard':
                this.onEnteringChooseInitialCard(args.args);
                this.showEvolutionsPopinPlayerButtons();
                break;
            case 'StartGame':
                this.showEvolutionsPopinPlayerButtons();
                break;
            case 'changeMimickedCard':
            case 'chooseMimickedCard':
            case 'changeMimickedCardWickednessTile':
            case 'chooseMimickedCardWickednessTile':
                this.setDiceSelectorVisibility(false);
                this.onEnteringChooseMimickedCard(args.args);
                break;
            case 'throwDice':
                this.setDiceSelectorVisibility(true);
                this.onEnteringThrowDice(args.args);
                break;
            case 'changeDie':
                this.setDiceSelectorVisibility(true);
                this.onEnteringChangeDie(args.args, this.isCurrentPlayerActive());
                break;
            case 'prepareResolveDice':
                this.setDiceSelectorVisibility(true);
                this.onEnteringPrepareResolveDice(args.args, this.isCurrentPlayerActive());
                break;
            case 'discardDie':
                this.setDiceSelectorVisibility(true);
                this.onEnteringDiscardDie(args.args);
                break;
            case 'selectExtraDie':
                this.setDiceSelectorVisibility(true);
                this.onEnteringSelectExtraDie(args.args);
                break;
            case 'discardKeepCard':
                this.onEnteringDiscardKeepCard(args.args);
                break;
            case 'resolveDice':
                this.falseBlessingAnkhAction = null;
                this.setDiceSelectorVisibility(true);
                this.onEnteringRerollOrDiscardDie(args.args);
                this.diceManager.hideLock();
                var argsResolveDice = args.args;
                if (argsResolveDice.isInHibernation) {
                    this.setGamestateDescription('Hibernation');
                }
                break;
            case 'rerollOrDiscardDie':
                this.setDiceSelectorVisibility(true);
                this.onEnteringRerollOrDiscardDie(args.args);
                break;
            case 'resolveNumberDice':
                this.setDiceSelectorVisibility(true);
                this.onEnteringResolveNumberDice(args.args);
                break;
            case 'takeWickednessTile':
                this.onEnteringTakeWickednessTile(args.args, this.isCurrentPlayerActive());
                break;
            case 'resolveHeartDiceAction':
                this.setDiceSelectorVisibility(true);
                this.onEnteringResolveHeartDice(args.args, this.isCurrentPlayerActive());
                break;
            case 'resolveSmashDiceAction':
                this.setDiceSelectorVisibility(true);
                this.onEnteringResolveSmashDice(args.args, this.isCurrentPlayerActive());
                break;
            case 'chooseEvolutionCard':
                this.onEnteringChooseEvolutionCard(args.args, this.isCurrentPlayerActive());
                break;
            case 'stealCostumeCard':
                this.setDiceSelectorVisibility(false);
                this.onEnteringStealCostumeCard(args.args, this.isCurrentPlayerActive());
                break;
            case 'leaveTokyoExchangeCard':
                this.setDiceSelectorVisibility(false);
                break;
            case 'buyCard':
                this.setDiceSelectorVisibility(false);
                this.onEnteringBuyCard(args.args, this.isCurrentPlayerActive());
                break;
            case 'sellCard':
                this.setDiceSelectorVisibility(false);
                this.onEnteringSellCard(args.args);
                break;
            case 'answerQuestion':
                this.onEnteringAnswerQuestion(args.args);
                break;
            case 'EndTurn':
                this.setDiceSelectorVisibility(false);
                this.onEnteringEndTurn();
                break;
        }
    };
    KingOfTokyo.prototype.showEvolutionsPopinPlayerButtons = function () {
        if (this.isPowerUpExpansion()) {
            Object.keys(this.gamedatas.players).forEach(function (playerId) { return document.getElementById("see-monster-evolution-player-".concat(playerId)).classList.toggle('visible', true); });
        }
    };
    KingOfTokyo.prototype.showActivePlayer = function (playerId) {
        this.playerTables.forEach(function (playerTable) { return playerTable.setActivePlayer(playerId == playerTable.playerId); });
    };
    KingOfTokyo.prototype.setGamestateDescription = function (property) {
        if (property === void 0) { property = ''; }
        var originalState = this.gamedatas.gamestates[this.gamedatas.gamestate.id];
        if (this.gamedatas.gamestate.description !== "".concat(originalState['description' + property])) {
            this.gamedatas.gamestate.description = "".concat(originalState['description' + property]);
            this.gamedatas.gamestate.descriptionmyturn = "".concat(originalState['descriptionmyturn' + property]);
            this.updatePageTitle();
        }
    };
    KingOfTokyo.prototype.removeGamestateDescription = function () {
        this.gamedatas.gamestate.description = '';
        this.gamedatas.gamestate.descriptionmyturn = '';
        this.updatePageTitle();
    };
    KingOfTokyo.prototype.onEnteringPickEvolutionForDeck = function (args) {
        var _this = this;
        if (!document.getElementById('choose-evolution-in')) {
            dojo.place("\n                <div class=\"whiteblock\">\n                    <h3>".concat(_("Choose an Evolution in"), "</h3>\n                    <div id=\"choose-evolution-in\" class=\"evolution-card-stock player-evolution-cards\"></div>\n                </div>\n                <div class=\"whiteblock\">\n                    <h3>").concat(_("Evolutions in your deck"), "</h3>\n                    <div id=\"evolutions-in-deck\" class=\"evolution-card-stock player-evolution-cards\"></div>\n                </div>\n            "), 'mutant-evolution-choice');
            this.choseEvolutionInStock = new LineStock(this.evolutionCardsManager, document.getElementById("choose-evolution-in"));
            this.choseEvolutionInStock.setSelectionMode('single');
            this.choseEvolutionInStock.onCardClick = function (card) { return _this.pickEvolutionForDeck(card.id); };
            this.inDeckEvolutionsStock = new LineStock(this.evolutionCardsManager, document.getElementById("evolutions-in-deck"));
        }
        this.choseEvolutionInStock.removeAll();
        this.choseEvolutionInStock.addCards(args._private.chooseCardIn);
        this.inDeckEvolutionsStock.addCards(args._private.inDeck.filter(function (card) { return !_this.inDeckEvolutionsStock.contains(card); }));
    };
    KingOfTokyo.prototype.onEnteringChooseInitialCard = function (args) {
        var suffix = '';
        if (args.chooseEvolution) {
            suffix = args.chooseCostume ? 'evocostume' : 'evo';
        }
        this.setGamestateDescription(suffix);
        if (args.chooseCostume) {
            this.tableCenter.setInitialCards(args.cards);
            this.tableCenter.setVisibleCardsSelectionClass(args.chooseEvolution);
        }
        if (this.isCurrentPlayerActive()) {
            this.tableCenter.setVisibleCardsSelectionMode('single');
            if (args.chooseEvolution) {
                var playerTable = this.getPlayerTable(this.getPlayerId());
                playerTable.showEvolutionPickStock(args._private.evolutions);
                playerTable.setVisibleCardsSelectionClass(args.chooseCostume);
            }
        }
    };
    KingOfTokyo.prototype.onEnteringStepEvolution = function (args) {
        console.log('onEnteringStepEvolution', args, this.isCurrentPlayerActive());
        if (this.isCurrentPlayerActive()) {
            var playerId_1 = this.getPlayerId();
            this.getPlayerTable(playerId_1).highlightHiddenEvolutions(args.highlighted.filter(function (card) { return card.location_arg === playerId_1; }));
        }
    };
    KingOfTokyo.prototype.onEnteringBeforeEndTurn = function (args) {
        if (args._private) {
            Object.keys(args._private).forEach(function (key) {
                var div = document.getElementById("hand-evolution-cards_item_".concat(key));
                if (div) {
                    var counter = args._private[key];
                    var symbol = SYMBOL_AS_STRING_PADDED[counter[1]];
                    div.insertAdjacentHTML('beforeend', formatTextIcons("<div class=\"evolution-inner-counter\">".concat(counter[0], " ").concat(symbol, "</div>")));
                }
            });
        }
    };
    KingOfTokyo.prototype.onEnteringThrowDice = function (args) {
        var _this = this;
        var _a, _b;
        this.setGamestateDescription(args.throwNumber >= args.maxThrowNumber ? "last" : '');
        this.diceManager.showLock();
        var isCurrentPlayerActive = this.isCurrentPlayerActive();
        this.diceManager.setDiceForThrowDice(args.dice, args.selectableDice, args.canHealWithDice, args.frozenFaces);
        if (isCurrentPlayerActive) {
            var orbOfDoomsSuffix = args.opponentsOrbOfDooms ? formatTextIcons(" (-".concat(args.opponentsOrbOfDooms, "[Heart])")) : '';
            if (args.throwNumber < args.maxThrowNumber) {
                this.createButton('dice-actions', 'rethrow_button', dojo.string.substitute(_("Reroll dice (${number} roll(s) remaining)"), { 'number': args.maxThrowNumber - args.throwNumber }) + orbOfDoomsSuffix, function () { return _this.onRethrow(); }, !args.dice.some(function (dice) { return !dice.locked; }));
                this.addTooltip('rethrow_button', _("Click on dice you want to keep to lock them, then click this button to reroll the others"), "".concat(_("Ctrl+click to move all dice with same value"), "<br>\n                    ").concat(_("Alt+click to move all dice but clicked die")));
            }
            if (args.rethrow3.hasCard) {
                this.createButton('dice-actions', 'rethrow3_button', _("Reroll") + formatTextIcons(' [dice3]') + ' (' + this.cardsManager.getCardName(5, 'text-only') + ')', function () { return _this.rethrow3(); }, !args.rethrow3.hasDice3);
            }
            if (((_a = args.energyDrink) === null || _a === void 0 ? void 0 : _a.hasCard) && args.throwNumber === args.maxThrowNumber) {
                this.createButton('dice-actions', 'buy_energy_drink_button', _("Get extra die Roll") + formatTextIcons(" ( 1[Energy])") + orbOfDoomsSuffix, function () { return _this.buyEnergyDrink(); });
                this.checkBuyEnergyDrinkState(args.energyDrink.playerEnergy);
            }
            if (args.hasSmokeCloud && args.throwNumber === args.maxThrowNumber) {
                this.createButton('dice-actions', 'use_smoke_cloud_button', _("Get extra die Roll") + " (<span class=\"smoke-cloud token\"></span>)" + orbOfDoomsSuffix, function () { return _this.useSmokeCloud(); });
            }
            if (args.hasCultist && args.throwNumber === args.maxThrowNumber) {
                this.createButton('dice-actions', 'use_cultist_button', _("Get extra die Roll") + " (".concat(_('Cultist'), ")") + orbOfDoomsSuffix, function () { return _this.useCultist(); });
            }
            if (args.rerollDie.isBeastForm) {
                dojo.place("<div id=\"beast-form-dice-actions\"></div>", 'dice-actions');
                var simpleFaces_1 = [];
                args.dice.filter(function (die) { return die.type < 2; }).forEach(function (die) {
                    if (die.canReroll && (die.type > 0 || !simpleFaces_1.includes(die.value))) {
                        var faceText = die.type == 1 ? BERSERK_DIE_STRINGS[die.value] : DICE_STRINGS[die.value];
                        _this.createButton('beast-form-dice-actions', "rerollDie".concat(die.id, "_button"), _("Reroll") + formatTextIcons(' ' + faceText) + ' (' + _this.cardsManager.getCardName(301, 'text-only', 1) + ')', function () { return _this.rerollDie(die.id); }, !args.rerollDie.canUseBeastForm);
                        if (die.type == 0) {
                            simpleFaces_1.push(die.value);
                        }
                    }
                });
            }
        }
        if (args.throwNumber === args.maxThrowNumber && !args.hasSmokeCloud && !args.hasCultist && !((_b = args.energyDrink) === null || _b === void 0 ? void 0 : _b.hasCard) && (!args.rerollDie.isBeastForm || !args.rerollDie.canUseBeastForm)) {
            this.diceManager.disableDiceAction();
        }
    };
    KingOfTokyo.prototype.onEnteringChangeDie = function (args, isCurrentPlayerActive) {
        var _this = this;
        var _a, _b;
        if ((_a = args.dice) === null || _a === void 0 ? void 0 : _a.length) {
            this.diceManager.setDiceForChangeDie(args.dice, args.selectableDice, args, args.canHealWithDice, args.frozenFaces);
        }
        if (isCurrentPlayerActive && args.dice && ((_b = args.rethrow3) === null || _b === void 0 ? void 0 : _b.hasCard)) {
            if (document.getElementById('rethrow3changeDie_button')) {
                dojo.toggleClass('rethrow3changeDie_button', 'disabled', !args.rethrow3.hasDice3);
            }
            else {
                this.createButton('dice-actions', 'rethrow3changeDie_button', _("Reroll") + formatTextIcons(' [dice3]'), function () { return _this.rethrow3changeDie(); }, !args.rethrow3.hasDice3);
            }
        }
    };
    KingOfTokyo.prototype.onEnteringPsychicProbeRollDie = function (args) {
        var _this = this;
        var _a;
        this.diceManager.setDiceForPsychicProbe(args.dice, args.selectableDice, args.canHealWithDice, args.frozenFaces);
        if (args.dice && ((_a = args.rethrow3) === null || _a === void 0 ? void 0 : _a.hasCard) && this.isCurrentPlayerActive()) {
            if (document.getElementById('rethrow3psychicProbe_button')) {
                dojo.toggleClass('rethrow3psychicProbe_button', 'disabled', !args.rethrow3.hasDice3);
            }
            else {
                this.createButton('dice-actions', 'rethrow3psychicProbe_button', _("Reroll") + formatTextIcons(' [dice3]'), function () { return _this.rethrow3psychicProbe(); }, !args.rethrow3.hasDice3);
            }
        }
    };
    KingOfTokyo.prototype.onEnteringDiscardDie = function (args) {
        var _a;
        if ((_a = args.dice) === null || _a === void 0 ? void 0 : _a.length) {
            this.diceManager.setDiceForDiscardDie(args.dice, args.selectableDice, args.canHealWithDice, args.frozenFaces);
        }
    };
    KingOfTokyo.prototype.onEnteringSelectExtraDie = function (args) {
        var _a;
        if ((_a = args.dice) === null || _a === void 0 ? void 0 : _a.length) {
            this.diceManager.setDiceForDiscardDie(args.dice, args.selectableDice, args.canHealWithDice, args.frozenFaces);
        }
    };
    KingOfTokyo.prototype.onEnteringRerollOrDiscardDie = function (args) {
        var _a;
        if ((_a = args.dice) === null || _a === void 0 ? void 0 : _a.length) {
            this.diceManager.setDiceForDiscardDie(args.dice, args.selectableDice, args.canHealWithDice, args.frozenFaces, 'rerollOrDiscard');
        }
    };
    KingOfTokyo.prototype.onEnteringRerollDice = function (args) {
        var _a;
        if ((_a = args.dice) === null || _a === void 0 ? void 0 : _a.length) {
            this.diceManager.setDiceForDiscardDie(args.dice, args.selectableDice, args.canHealWithDice, args.frozenFaces, 'rerollDice');
        }
    };
    KingOfTokyo.prototype.onEnteringPrepareResolveDice = function (args, isCurrentPlayerActive) {
        var _a;
        if (args.hasEncasedInIce) {
            this.setGamestateDescription('EncasedInIce');
        }
        if ((_a = args.dice) === null || _a === void 0 ? void 0 : _a.length) {
            this.diceManager.setDiceForDiscardDie(args.dice, isCurrentPlayerActive ? args.selectableDice : [], args.canHealWithDice, args.frozenFaces, 'freezeDie');
        }
    };
    KingOfTokyo.prototype.onEnteringDiscardKeepCard = function (args) {
        var _this = this;
        if (this.isCurrentPlayerActive()) {
            this.playerTables.filter(function (playerTable) { return playerTable.playerId === _this.getPlayerId(); }).forEach(function (playerTable) { return playerTable.cards.setSelectionMode('single'); });
            args.disabledIds.forEach(function (id) { var _a; return (_a = document.querySelector("div[id$=\"_item_".concat(id, "\"]"))) === null || _a === void 0 ? void 0 : _a.classList.add('disabled'); });
        }
    };
    KingOfTokyo.prototype.onEnteringResolveNumberDice = function (args) {
        var _a;
        if ((_a = args.dice) === null || _a === void 0 ? void 0 : _a.length) {
            this.diceManager.setDiceForSelectHeartAction(args.dice, args.selectableDice, args.canHealWithDice, args.frozenFaces);
        }
    };
    KingOfTokyo.prototype.onEnteringTakeWickednessTile = function (args, isCurrentPlayerActive) {
        var _a;
        this.tableCenter.setWickednessTilesSelectable(args.level, true, isCurrentPlayerActive);
        if ((_a = args.dice) === null || _a === void 0 ? void 0 : _a.length) {
            this.diceManager.setDiceForSelectHeartAction(args.dice, args.selectableDice, args.canHealWithDice, args.frozenFaces);
        }
    };
    KingOfTokyo.prototype.onEnteringResolveHeartDice = function (args, isCurrentPlayerActive) {
        var _a;
        if (args.skipped) {
            this.removeGamestateDescription();
        }
        if ((_a = args.dice) === null || _a === void 0 ? void 0 : _a.length) {
            this.diceManager.setDiceForSelectHeartAction(args.dice, args.selectableDice, args.canHealWithDice, args.frozenFaces);
            if (isCurrentPlayerActive) {
                dojo.place("<div id=\"heart-action-selector\" class=\"whiteblock action-selector\"></div>", 'rolled-dice-and-rapid-actions', 'after');
                new HeartActionSelector(this, 'heart-action-selector', args);
            }
        }
    };
    KingOfTokyo.prototype.onEnteringResolveSmashDice = function (args, isCurrentPlayerActive) {
        var _a;
        if (args.skipped) {
            this.removeGamestateDescription();
        }
        if ((_a = args.dice) === null || _a === void 0 ? void 0 : _a.length) {
            this.diceManager.setDiceForSelectHeartAction(args.dice, args.selectableDice, args.canHealWithDice, args.frozenFaces);
            if (isCurrentPlayerActive) {
                dojo.place("<div id=\"smash-action-selector\" class=\"whiteblock action-selector\"></div>", 'rolled-dice-and-rapid-actions', 'after');
                new SmashActionSelector(this, 'smash-action-selector', args);
            }
        }
    };
    KingOfTokyo.prototype.onEnteringCancelDamage = function (args, isCurrentPlayerActive) {
        var _this = this;
        var _a;
        if (args.dice) {
            this.diceManager.showCamouflageRoll(args.dice);
        }
        if (!args.canCancelDamage && args.canHealToAvoidDeath) {
            this.setGamestateDescription('HealBeforeDamage');
        }
        else if (args.canCancelDamage) {
            this.setGamestateDescription('Reduce');
        }
        if (isCurrentPlayerActive) {
            if (args.dice && ((_a = args.rethrow3) === null || _a === void 0 ? void 0 : _a.hasCard)) {
                if (document.getElementById('rethrow3camouflage_button')) {
                    dojo.toggleClass('rethrow3camouflage_button', 'disabled', !args.rethrow3.hasDice3);
                }
                else {
                    this.createButton('dice-actions', 'rethrow3camouflage_button', _("Reroll") + formatTextIcons(' [dice3]'), function () { return _this.rethrow3camouflage(); }, !args.rethrow3.hasDice3);
                }
            }
            if (args.canThrowDices && !document.getElementById('throwCamouflageDice_button')) {
                this.addActionButton('throwCamouflageDice_button', _("Throw dice"), 'throwCamouflageDice');
            }
            else if (!args.canThrowDices && document.getElementById('throwCamouflageDice_button')) {
                dojo.destroy('throwCamouflageDice_button');
            }
            if (args.canUseWings && !document.getElementById('useWings_button')) {
                this.addActionButton('useWings_button', formatTextIcons(dojo.string.substitute(_("Use ${card_name}") + " ( 2[Energy] )", { 'card_name': this.cardsManager.getCardName(48, 'text-only') })), function () { return _this.useWings(); });
                document.getElementById('useWings_button').dataset.enableAtEnergy = '2';
                if (args.playerEnergy < 2) {
                    dojo.addClass('useWings_button', 'disabled');
                }
            }
            if (args.canUseDetachableTail && !document.getElementById('useDetachableTail_button')) {
                this.addActionButton('useDetachableTail_button', dojo.string.substitute(_("Use ${card_name}"), { 'card_name': this.evolutionCardsManager.getCardName(51, 'text-only') }), function () { return _this.useInvincibleEvolution(51); });
            }
            if (args.canUseRabbitsFoot && !document.getElementById('useRabbitsFoot_button')) {
                this.addActionButton('useRabbitsFoot_button', dojo.string.substitute(_("Use ${card_name}"), { 'card_name': this.evolutionCardsManager.getCardName(143, 'text-only') }), function () { return _this.useInvincibleEvolution(143); });
            }
            if (args.canUseCandy && !document.getElementById('useCandy_button')) {
                this.addActionButton('useCandy_button', dojo.string.substitute(_("Use ${card_name}"), { 'card_name': this.evolutionCardsManager.getCardName(88, 'text-only') }), function () { return _this.useCandyEvolution(); });
            }
            if (args.countSuperJump > 0 && !document.getElementById('useSuperJump1_button')) {
                Object.keys(args.replaceHeartByEnergyCost).filter(function (energy) { return Number(energy) <= args.countSuperJump; }).forEach(function (energy) {
                    var energyCost = Number(energy);
                    var remainingDamage = args.replaceHeartByEnergyCost[energy];
                    var id = "useSuperJump".concat(energyCost, "_button");
                    if (!document.getElementById(id)) {
                        _this.addActionButton(id, formatTextIcons(dojo.string.substitute(_("Use ${card_name}") + ' : ' + _("lose ${number}[energy] instead of ${number}[heart]"), { 'number': energyCost, 'card_name': _this.cardsManager.getCardName(53, 'text-only') }) + (remainingDamage > 0 ? " (-".concat(remainingDamage, "[Heart])") : '')), function () { return _this.useSuperJump(energyCost); });
                        document.getElementById(id).dataset.enableAtEnergy = '' + energyCost;
                        dojo.toggleClass(id, 'disabled', args.playerEnergy < energyCost);
                    }
                });
            }
            if (args.canUseRobot && !document.getElementById('useRobot1_button')) {
                Object.keys(args.replaceHeartByEnergyCost).forEach(function (energy) {
                    var energyCost = Number(energy);
                    var remainingDamage = args.replaceHeartByEnergyCost[energy];
                    var id = "useRobot".concat(energyCost, "_button");
                    if (!document.getElementById(id)) {
                        _this.addActionButton(id, formatTextIcons(dojo.string.substitute(_("Use ${card_name}") + ' : ' + _("lose ${number}[energy] instead of ${number}[heart]"), { 'number': energyCost, 'card_name': _this.cardsManager.getCardName(210, 'text-only') }) + (remainingDamage > 0 ? " (-".concat(remainingDamage, "[Heart])") : '')), function () { return _this.useRobot(energyCost); });
                        document.getElementById(id).dataset.enableAtEnergy = '' + energyCost;
                        dojo.toggleClass(id, 'disabled', args.playerEnergy < energyCost);
                    }
                });
            }
            if (args.canUseElectricArmor && !document.getElementById('useElectricArmor_button')) {
                Object.keys(args.replaceHeartByEnergyCost).forEach(function (energy) {
                    var energyCost = Number(energy);
                    var remainingDamage = args.replaceHeartByEnergyCost[energy];
                    var id = "useElectricArmor".concat(energyCost, "_button");
                    if (!document.getElementById(id) && energyCost == 1) {
                        _this.addActionButton(id, formatTextIcons(dojo.string.substitute(_("Use ${card_name}") + ' : ' + _("lose ${number}[energy] instead of ${number}[heart]"), { 'number': energyCost, 'card_name': _this.cardsManager.getCardName(58, 'text-only') }) + (remainingDamage > 0 ? " (-".concat(remainingDamage, "[Heart])") : '')), function () { return _this.useElectricArmor(energyCost); });
                        document.getElementById(id).dataset.enableAtEnergy = '' + energyCost;
                        dojo.toggleClass(id, 'disabled', args.playerEnergy < energyCost);
                    }
                });
            }
            if (!args.canThrowDices && !document.getElementById('skipWings_button')) {
                var canAvoidDeath_1 = args.canDoAction && args.skipMeansDeath && (args.canCancelDamage || args.canHealToAvoidDeath);
                this.addActionButton('skipWings_button', args.canUseWings ? dojo.string.substitute(_("Don't use ${card_name}"), { 'card_name': this.cardsManager.getCardName(48, 'text-only') }) : _("Skip"), function () {
                    if (canAvoidDeath_1) {
                        _this.confirmationDialog(formatTextIcons(_("Are you sure you want to Skip? It means [Skull]")), function () { return _this.skipWings(); });
                    }
                    else {
                        _this.skipWings();
                    }
                }, null, null, canAvoidDeath_1 ? 'red' : undefined);
                if (!args.canDoAction) {
                    this.startActionTimer('skipWings_button', ACTION_TIMER_DURATION);
                }
            }
            var rapidHealingSyncButtons = document.querySelectorAll("[id^='rapidHealingSync_button'");
            rapidHealingSyncButtons.forEach(function (rapidHealingSyncButton) { return rapidHealingSyncButton.parentElement.removeChild(rapidHealingSyncButton); });
            if (args.canHeal && args.damageToCancelToSurvive > 0) {
                var _loop_5 = function (i) {
                    var cultistCount = i;
                    var rapidHealingCount = args.rapidHealingHearts > 0 ? args.canHeal - cultistCount : 0;
                    var cardsNames = [];
                    if (cultistCount > 0) {
                        cardsNames.push(_('Cultist'));
                    }
                    if (rapidHealingCount > 0) {
                        cardsNames.push(_(this_2.cardsManager.getCardName(37, 'text-only')));
                    }
                    if (cultistCount + rapidHealingCount >= args.damageToCancelToSurvive && 2 * rapidHealingCount <= args.playerEnergy) {
                        var text = dojo.string.substitute(_("Use ${card_name}") + " : " + formatTextIcons("".concat(_('Gain ${hearts}[Heart]')) + (rapidHealingCount > 0 ? " (".concat(2 * rapidHealingCount, "[Energy])") : '')), { 'card_name': cardsNames.join(', '), 'hearts': cultistCount + rapidHealingCount });
                        this_2.addActionButton("rapidHealingSync_button_".concat(i), text, function () { return _this.useRapidHealingSync(cultistCount, rapidHealingCount); });
                    }
                };
                var this_2 = this;
                //this.rapidHealingSyncHearts = args.rapidHealingHearts;
                for (var i = Math.min(args.rapidHealingCultists, args.canHeal); i >= 0; i--) {
                    _loop_5(i);
                }
            }
        }
    };
    KingOfTokyo.prototype.onEnteringChooseEvolutionCard = function (args, isCurrentPlayerActive) {
        if (isCurrentPlayerActive) {
            this.getPlayerTable(this.getPlayerId()).showEvolutionPickStock(args._private.evolutions);
        }
    };
    KingOfTokyo.prototype.onEnteringStealCostumeCard = function (args, isCurrentPlayerActive) {
        var _this = this;
        var _a;
        if (!args.canGiveGift && !args.canBuyFromPlayers && !this.isHalloweenExpansion()) {
            this.setGamestateDescription('Give');
        }
        if (args.canGiveGift) {
            this.setGamestateDescription(args.canBuyFromPlayers ? "StealAndGive" : 'Give');
            if (isCurrentPlayerActive) {
                (_a = this.getPlayerTable(this.getPlayerId()).visibleEvolutionCards) === null || _a === void 0 ? void 0 : _a.setSelectionMode('single');
            }
        }
        if (isCurrentPlayerActive) {
            if (args.canBuyFromPlayers) {
                this.playerTables.filter(function (playerTable) { return playerTable.playerId != _this.getPlayerId(); }).forEach(function (playerTable) { return playerTable.cards.setSelectionMode('single'); });
                this.setBuyDisabledCard(args);
            }
            var playerId_2 = this.getPlayerId();
            this.getPlayerTable(playerId_2).highlightHiddenEvolutions(args.highlighted.filter(function (card) { return card.location_arg === playerId_2; }));
            this.getPlayerTable(playerId_2).highlightVisibleEvolutions(args.tableGifts);
        }
    };
    KingOfTokyo.prototype.onEnteringExchangeCard = function (args, isCurrentPlayerActive) {
        var _this = this;
        if (isCurrentPlayerActive) {
            args.disabledIds.forEach(function (id) {
                var cardDiv = _this.cardsManager.getCardElement({ id: id });
                cardDiv === null || cardDiv === void 0 ? void 0 : cardDiv.classList.add('bga-cards_disabled-card');
            });
        }
    };
    KingOfTokyo.prototype.onEnteringBuyCard = function (args, isCurrentPlayerActive) {
        var _a, _b;
        if (isCurrentPlayerActive) {
            var stateName = this.getStateName();
            var bamboozle = stateName === 'answerQuestion' && this.gamedatas.gamestate.args.question.code === 'Bamboozle';
            var playerId_3 = this.getPlayerId();
            if (bamboozle) {
                playerId_3 = this.gamedatas.gamestate.args.question.args.cardBeingBought.playerId;
            }
            this.tableCenter.setVisibleCardsSelectionMode('single');
            if (this.isPowerUpExpansion()) {
                this.getPlayerTable(playerId_3).reservedCards.setSelectionMode('single');
            }
            this.playerTables.forEach(function (playerTable) { return playerTable.cards.setSelectionMode(args.canBuyFromPlayers && playerTable.playerId != playerId_3 ? 'single' : 'none'); });
            if ((_b = (_a = args._private) === null || _a === void 0 ? void 0 : _a.pickCards) === null || _b === void 0 ? void 0 : _b.length) {
                this.tableCenter.showPickStock(args._private.pickCards);
            }
            this.setBuyDisabledCard(args);
        }
    };
    KingOfTokyo.prototype.onEnteringChooseMimickedCard = function (args) {
        if (this.isCurrentPlayerActive()) {
            this.playerTables.forEach(function (playerTable) { return playerTable.cards.setSelectionMode('single'); });
            this.setBuyDisabledCard(args);
        }
    };
    KingOfTokyo.prototype.onEnteringSellCard = function (args) {
        var _this = this;
        if (this.isCurrentPlayerActive()) {
            this.playerTables.filter(function (playerTable) { return playerTable.playerId === _this.getPlayerId(); }).forEach(function (playerTable) { return playerTable.cards.setSelectionMode('single'); });
            args.disabledIds.forEach(function (id) { var _a; return (_a = document.querySelector("div[id$=\"_item_".concat(id, "\"]"))) === null || _a === void 0 ? void 0 : _a.classList.add('disabled'); });
        }
    };
    KingOfTokyo.prototype.onEnteringAnswerQuestion = function (args) {
        var _this = this;
        var question = args.question;
        this.gamedatas.gamestate.description = question.description;
        this.gamedatas.gamestate.descriptionmyturn = question.descriptionmyturn;
        this.updatePageTitle();
        switch (question.code) {
            case 'ChooseMimickedCard':
                this.onEnteringChooseMimickedCard(question.args.mimicArgs);
                break;
            case 'Bamboozle':
                var bamboozleArgs = question.args;
                this.onEnteringBuyCard(bamboozleArgs.buyCardArgs, this.isCurrentPlayerActive());
                break;
            case 'GazeOfTheSphinxSnake':
                if (this.isCurrentPlayerActive()) {
                    this.getPlayerTable(this.getPlayerId()).visibleEvolutionCards.setSelectionMode('single');
                }
                break;
            case 'IcyReflection':
                if (this.isCurrentPlayerActive()) {
                    var icyReflectionArgs = question.args;
                    this.playerTables.forEach(function (playerTable) { return playerTable.visibleEvolutionCards.setSelectionMode('single'); });
                    icyReflectionArgs.disabledEvolutions.forEach(function (evolution) {
                        var cardDiv = document.querySelector("div[id$=\"_item_".concat(evolution.id, "\"]"));
                        if (cardDiv && cardDiv.closest('.player-evolution-cards') !== null) {
                            cardDiv.classList.add('disabled');
                        }
                    });
                }
                break;
            case 'MiraculousCatch':
                var miraculousCatchArgs = question.args;
                dojo.place("<div id=\"title-bar-stock\" class=\"card-in-title-wrapper\"></div>", "maintitlebar_content");
                this.titleBarStock = new LineStock(this.cardsManager, document.getElementById('title-bar-stock'));
                this.titleBarStock.addCard(miraculousCatchArgs.card);
                this.titleBarStock.setSelectionMode('single');
                this.titleBarStock.onCardClick = function () { return _this.buyCardMiraculousCatch(); };
                break;
            case 'DeepDive':
                var deepDiveCatchArgs = question.args;
                dojo.place("<div id=\"title-bar-stock\" class=\"card-in-title-wrapper\"></div>", "maintitlebar_content");
                this.titleBarStock = new LineStock(this.cardsManager, document.getElementById('title-bar-stock'));
                this.titleBarStock.addCards(deepDiveCatchArgs.cards, { fromStock: this.tableCenter.getDeck(), originalSide: 'back', rotationDelta: 90 }, undefined, true);
                this.titleBarStock.setSelectionMode('single');
                this.titleBarStock.onCardClick = function (card) { return _this.playCardDeepDive(card.id); };
                break;
            case 'MyToy':
                this.tableCenter.setVisibleCardsSelectionMode('single');
                break;
            case 'SuperiorAlienTechnology':
                var superiorAlienTechnologyArgs = question.args;
                this.setTitleBarSuperiorAlienTechnologyCard(superiorAlienTechnologyArgs.card);
                this.setDiceSelectorVisibility(false);
                break;
            case 'FreezeRayChooseOpponent':
                var argsFreezeRayChooseOpponent = question.args;
                argsFreezeRayChooseOpponent.smashedPlayersIds.forEach(function (playerId) {
                    var player = _this.gamedatas.players[playerId];
                    var label = "<div class=\"monster-icon monster".concat(player.monster, "\" style=\"background-color: ").concat(player.monster > 100 ? 'unset' : '#' + player.color, ";\"></div> ").concat(player.name);
                    _this.addActionButton("freezeRayChooseOpponent_button_".concat(playerId), label, function () { return _this.freezeRayChooseOpponent(playerId); });
                });
                break;
        }
    };
    KingOfTokyo.prototype.onEnteringEndTurn = function () {
    };
    KingOfTokyo.prototype.onLeavingState = function (stateName) {
        var _a;
        log('Leaving state: ' + stateName);
        if (this.isPowerUpExpansion()) {
            var evolutionCardsSingleState = this.gamedatas.EVOLUTION_CARDS_SINGLE_STATE[stateName];
            if (evolutionCardsSingleState) {
                (_a = this.getPlayerTable(this.getPlayerId())) === null || _a === void 0 ? void 0 : _a.setEvolutionCardsSingleState(evolutionCardsSingleState, false);
            }
        }
        switch (stateName) {
            case 'chooseInitialCard':
                this.tableCenter.setVisibleCardsSelectionMode('none');
                this.tableCenter.setVisibleCardsSelectionClass(false);
                this.playerTables.forEach(function (playerTable) {
                    playerTable.hideEvolutionPickStock();
                    playerTable.setVisibleCardsSelectionClass(false);
                });
                break;
            case 'beforeStartTurn':
            case 'beforeResolveDice':
            case 'beforeEnteringTokyo':
            case 'afterEnteringTokyo':
            case 'cardIsBought':
                this.onLeavingStepEvolution();
                break;
            case 'beforeEndTurn':
                this.onLeavingStepEvolution();
                this.onLeavingBeforeEndTurn();
                break;
            case 'changeMimickedCard':
            case 'chooseMimickedCard':
            case 'opportunistChooseMimicCard':
            case 'chooseMimickedCardWickednessTile':
            case 'changeMimickedCardWickednessTile':
                this.onLeavingChooseMimickedCard();
                break;
            case 'throwDice':
                document.getElementById('dice-actions').innerHTML = '';
                break;
            case 'changeActivePlayerDie':
            case 'psychicProbeRollDie': // TODO remove
                if (document.getElementById('rethrow3psychicProbe_button')) {
                    dojo.destroy('rethrow3psychicProbe_button');
                }
                break;
            case 'changeDie':
                if (document.getElementById('rethrow3changeDie_button')) {
                    dojo.destroy('rethrow3changeDie_button');
                }
                this.diceManager.removeAllBubbles();
                break;
            case 'discardKeepCard':
                this.onLeavingSellCard();
                break;
            case 'rerollDice':
                this.diceManager.removeSelection();
                break;
            case 'takeWickednessTile':
                this.onLeavingTakeWickednessTile();
                break;
            case 'resolveHeartDiceAction':
                if (document.getElementById('heart-action-selector')) {
                    dojo.destroy('heart-action-selector');
                }
                break;
            case 'resolveSmashDiceAction':
                if (document.getElementById('smash-action-selector')) {
                    dojo.destroy('smash-action-selector');
                }
                break;
            case 'resolveSmashDice':
                this.diceManager.removeAllDice();
                break;
            case 'chooseEvolutionCard':
                this.playerTables.forEach(function (playerTable) { return playerTable.hideEvolutionPickStock(); });
                break;
            case 'leaveTokyo':
                this.removeSkipBuyPhaseToggle();
                break;
            case 'leaveTokyoExchangeCard':
            case 'buyCard':
            case 'opportunistBuyCard':
                this.onLeavingBuyCard();
                break;
            case 'stealCostumeCard':
                this.onLeavingStealCostumeCard();
                break;
            case 'cardIsBought':
                this.onLeavingStepEvolution();
                break;
            case 'sellCard':
                this.onLeavingSellCard();
                break;
            case 'cancelDamage':
                this.diceManager.removeAllDice();
                if (document.getElementById('rethrow3camouflage_button')) {
                    dojo.destroy('rethrow3camouflage_button');
                }
                break;
            case 'answerQuestion':
                this.onLeavingAnswerQuestion();
                if (this.gamedatas.gamestate.args.question.code === 'Bamboozle') {
                    this.onLeavingBuyCard();
                }
                break;
            case 'MyToy':
                this.tableCenter.setVisibleCardsSelectionMode('none');
                break;
        }
    };
    KingOfTokyo.prototype.onLeavingStepEvolution = function () {
        var _a;
        var playerId = this.getPlayerId();
        (_a = this.getPlayerTable(playerId)) === null || _a === void 0 ? void 0 : _a.unhighlightHiddenEvolutions();
    };
    KingOfTokyo.prototype.onLeavingBeforeEndTurn = function () {
        Array.from(document.querySelectorAll(".evolution-inner-counter")).forEach(function (elem) {
            var _a;
            (_a = elem === null || elem === void 0 ? void 0 : elem.parentElement) === null || _a === void 0 ? void 0 : _a.removeChild(elem);
        });
    };
    KingOfTokyo.prototype.onLeavingTakeWickednessTile = function () {
        this.tableCenter.setWickednessTilesSelectable(null, false, false);
    };
    KingOfTokyo.prototype.onLeavingBuyCard = function () {
        this.tableCenter.setVisibleCardsSelectionMode('none');
        dojo.query('.stockitem').removeClass('disabled');
        this.playerTables.forEach(function (playerTable) { return playerTable.cards.setSelectionMode('none'); });
        this.tableCenter.hidePickStock();
    };
    KingOfTokyo.prototype.onLeavingStealCostumeCard = function () {
        var _a;
        this.onLeavingBuyCard();
        var playerId = this.getPlayerId();
        var playerTable = this.getPlayerTable(playerId);
        if (playerTable) {
            playerTable.unhighlightHiddenEvolutions();
            playerTable.unhighlightVisibleEvolutions();
            (_a = playerTable.visibleEvolutionCards) === null || _a === void 0 ? void 0 : _a.setSelectionMode('none');
        }
    };
    KingOfTokyo.prototype.onLeavingChooseMimickedCard = function () {
        dojo.query('.stockitem').removeClass('disabled');
        this.playerTables.forEach(function (playerTable) { return playerTable.cards.setSelectionMode('none'); });
    };
    KingOfTokyo.prototype.onLeavingSellCard = function () {
        var _this = this;
        if (this.isCurrentPlayerActive()) {
            this.playerTables.filter(function (playerTable) { return playerTable.playerId === _this.getPlayerId(); }).forEach(function (playerTable) { return playerTable.cards.setSelectionMode('none'); });
            dojo.query('.stockitem').removeClass('disabled');
        }
    };
    KingOfTokyo.prototype.onLeavingAnswerQuestion = function () {
        var _a;
        var question = this.gamedatas.gamestate.args.question;
        switch (question.code) {
            case 'ChooseMimickedCard':
                this.onLeavingChooseMimickedCard();
                break;
            case 'Bamboozle':
                this.onLeavingBuyCard();
                break;
            case 'GazeOfTheSphinxSnake':
                if (this.isCurrentPlayerActive()) {
                    this.getPlayerTable(this.getPlayerId()).visibleEvolutionCards.setSelectionMode('none');
                }
                break;
            case 'IcyReflection':
                if (this.isCurrentPlayerActive()) {
                    this.playerTables.forEach(function (playerTable) { return playerTable.visibleEvolutionCards.setSelectionMode('none'); });
                    dojo.query('.stockitem').removeClass('disabled');
                }
                break;
            case 'MiraculousCatch':
            case 'DeepDive':
            case 'SuperiorAlienTechnology':
                this.titleBarStock.removeAll();
                (_a = document.getElementById("title-bar-stock")) === null || _a === void 0 ? void 0 : _a.remove();
                break;
        }
    };
    // onUpdateActionButtons: in this method you can manage "action buttons" that are displayed in the
    //                        action status bar (ie: the HTML links in the status bar).
    //
    KingOfTokyo.prototype.onUpdateActionButtons = function (stateName, args) {
        var _this = this;
        var _a, _b, _c;
        switch (stateName) {
            case 'beforeStartTurn':
            case 'beforeResolveDice':
            case 'beforeEnteringTokyo':
            case 'afterEnteringTokyo':
            case 'cardIsBought':
                this.onEnteringStepEvolution(args); // because it's multiplayer, enter action must be set here
                break;
            case 'beforeEndTurn':
                this.onEnteringStepEvolution(args); // because it's multiplayer, enter action must be set here
                this.onEnteringBeforeEndTurn(args);
                break;
            case 'changeActivePlayerDie':
            case 'psychicProbeRollDie':
                this.setDiceSelectorVisibility(true);
                this.onEnteringPsychicProbeRollDie(args); // because it's multiplayer, enter action must be set here
                break;
            case 'rerollDice':
                this.setDiceSelectorVisibility(true);
                this.onEnteringRerollDice(args);
                break;
            case 'cheerleaderSupport':
                this.setDiceSelectorVisibility(true);
                this.onEnteringPsychicProbeRollDie(args); // because it's multiplayer, enter action must be set here
                break;
            case 'leaveTokyo':
                this.setDiceSelectorVisibility(false);
                var argsLeaveTokyo = args;
                if (argsLeaveTokyo._private) {
                    this.addSkipBuyPhaseToggle(argsLeaveTokyo._private.skipBuyPhase);
                }
                break;
            case 'opportunistBuyCard':
                this.setDiceSelectorVisibility(false);
                break;
            case 'opportunistChooseMimicCard':
                this.setDiceSelectorVisibility(false);
                break;
            case 'cancelDamage':
                var argsCancelDamage = args;
                this.setDiceSelectorVisibility(argsCancelDamage.canThrowDices || !!argsCancelDamage.dice);
                this.onEnteringCancelDamage(argsCancelDamage, this.isCurrentPlayerActive());
                break;
        }
        if (this.isCurrentPlayerActive()) {
            switch (stateName) {
                case 'chooseInitialCard':
                    if (this.isInitialCardDoubleSelection()) {
                        this.addActionButton('confirmInitialCards_button', _("Confirm"), function () {
                            var _a, _b;
                            return _this.chooseInitialCard(Number((_a = _this.tableCenter.getVisibleCards().getSelection()[0]) === null || _a === void 0 ? void 0 : _a.id), Number((_b = _this.getPlayerTable(_this.getPlayerId()).pickEvolutionCards.getSelection()[0]) === null || _b === void 0 ? void 0 : _b.id));
                        });
                        document.getElementById("confirmInitialCards_button").classList.add('disabled');
                    }
                    break;
                case 'beforeStartTurn':
                    this.addActionButton('skipBeforeStartTurn_button', _("Skip"), function () { return _this.skipBeforeStartTurn(); });
                    break;
                case 'beforeEndTurn':
                    this.addActionButton('skipBeforeEndTurn_button', _("Skip"), function () { return _this.skipBeforeEndTurn(); });
                    break;
                case 'changeMimickedCardWickednessTile':
                    this.addActionButton('skipChangeMimickedCardWickednessTile_button', _("Skip"), function () { return _this.skipChangeMimickedCardWickednessTile(); });
                    if (!args.canChange) {
                        this.startActionTimer('skipChangeMimickedCardWickednessTile_button', ACTION_TIMER_DURATION);
                    }
                    break;
                case 'changeMimickedCard':
                    this.addActionButton('skipChangeMimickedCard_button', _("Skip"), function () { return _this.skipChangeMimickedCard(); });
                    if (!args.canChange) {
                        this.startActionTimer('skipChangeMimickedCard_button', ACTION_TIMER_DURATION);
                    }
                    break;
                case 'giveSymbolToActivePlayer':
                    var argsGiveSymbolToActivePlayer_1 = args;
                    var SYMBOL_AS_STRING_1 = ['[Heart]', '[Energy]', '[Star]'];
                    [4, 5, 0].forEach(function (symbol, symbolIndex) {
                        _this.addActionButton("giveSymbolToActivePlayer_button".concat(symbol), formatTextIcons(dojo.string.substitute(_("Give ${symbol}"), { symbol: SYMBOL_AS_STRING_1[symbolIndex] })), function () { return _this.giveSymbolToActivePlayer(symbol); });
                        if (!argsGiveSymbolToActivePlayer_1.canGive[symbol]) {
                            dojo.addClass("giveSymbolToActivePlayer_button".concat(symbol), 'disabled');
                        }
                    });
                    document.getElementById("giveSymbolToActivePlayer_button5").dataset.enableAtEnergy = '1';
                    break;
                case 'throwDice':
                    this.addActionButton('goToChangeDie_button', _("Resolve dice"), function () { return _this.goToChangeDie(); }, null, null, 'red');
                    var argsThrowDice = args;
                    if (!argsThrowDice.hasActions) {
                        this.startActionTimer('goToChangeDie_button', ACTION_TIMER_DURATION);
                    }
                    break;
                case 'changeDie':
                    var argsChangeDie = args;
                    if (argsChangeDie.hasYinYang) {
                        this.addActionButton('useYinYang_button', dojo.string.substitute(_('Use ${card_name}'), { card_name: this.evolutionCardsManager.getCardName(138, 'text-only') }), function () { return _this.useYinYang(); });
                    }
                    this.addActionButton('resolve_button', _("Resolve dice"), function () { return _this.resolveDice(); }, null, null, 'red');
                    break;
                case 'changeActivePlayerDie':
                case 'psychicProbeRollDie':
                    this.addActionButton('changeActivePlayerDieSkip_button', _("Skip"), function () { return _this.changeActivePlayerDieSkip(); });
                    break;
                case 'cheerleaderSupport':
                    this.addActionButton('support_button', formatTextIcons(_("Support (add [diceSmash] )")), function () { return _this.support(); });
                    this.addActionButton('dontSupport_button', _("Don't support"), function () { return _this.dontSupport(); });
                    break;
                case 'giveGoldenScarab':
                    var argsGiveGoldenScarab = args;
                    argsGiveGoldenScarab.playersIds.forEach(function (playerId) {
                        var player = _this.gamedatas.players[playerId];
                        var label = "<div class=\"monster-icon monster".concat(player.monster, "\" style=\"background-color: ").concat(player.monster > 100 ? 'unset' : '#' + player.color, ";\"></div> ").concat(player.name);
                        _this.addActionButton("giveGoldenScarab_button_".concat(playerId), label, function () { return _this.giveGoldenScarab(playerId); });
                    });
                    break;
                case 'giveSymbols':
                    var argsGiveSymbols = args;
                    argsGiveSymbols.combinations.forEach(function (combination, combinationIndex) {
                        var symbols = SYMBOL_AS_STRING_PADDED[combination[0]] + (combination.length > 1 ? SYMBOL_AS_STRING_PADDED[combination[1]] : '');
                        _this.addActionButton("giveSymbols_button".concat(combinationIndex), formatTextIcons(dojo.string.substitute(_("Give ${symbol}"), { symbol: symbols })), function () { return _this.giveSymbols(combination); });
                    });
                    break;
                case 'selectExtraDie':
                    var _loop_6 = function (face) {
                        this_3.addActionButton("selectExtraDie_button".concat(face), formatTextIcons(DICE_STRINGS[face]), function () { return _this.selectExtraDie(face); });
                    };
                    var this_3 = this;
                    for (var face = 1; face <= 6; face++) {
                        _loop_6(face);
                    }
                    break;
                case 'rerollOrDiscardDie':
                    this.addActionButton('falseBlessingReroll_button', _("Reroll"), function () {
                        dojo.addClass('falseBlessingReroll_button', 'action-button-toggle-button-selected');
                        dojo.removeClass('falseBlessingDiscard_button', 'action-button-toggle-button-selected');
                        _this.falseBlessingAnkhAction = 'actFalseBlessingReroll';
                    }, null, null, 'gray');
                    this.addActionButton('falseBlessingDiscard_button', _("Discard"), function () {
                        dojo.addClass('falseBlessingDiscard_button', 'action-button-toggle-button-selected');
                        dojo.removeClass('falseBlessingReroll_button', 'action-button-toggle-button-selected');
                        _this.falseBlessingAnkhAction = 'actFalseBlessingDiscard';
                    }, null, null, 'gray');
                    this.addActionButton('falseBlessingSkip_button', _("Skip"), function () { return _this.falseBlessingSkip(); });
                    break;
                case 'rerollDice':
                    var argsRerollDice = args;
                    this.addActionButton('rerollDice_button', _("Reroll selected dice"), function () { return _this.rerollDice(_this.diceManager.getSelectedDiceIds()); });
                    dojo.addClass('rerollDice_button', 'disabled');
                    if (argsRerollDice.min === 0) {
                        this.addActionButton('skipRerollDice_button', _("Skip"), function () { return _this.rerollDice([]); });
                    }
                    break;
                case 'AskMindbug':
                    this.statusBar.addActionButton(/*TODOMB_*/ ('Mindbug!'), function () { return _this.bgaPerformAction('actMindbug'); }, { color: 'alert' });
                    this.statusBar.addActionButton(_('Skip'), function () { return _this.bgaPerformAction('actPassMindbug'); });
                    break;
                case 'resolveDice':
                    var argsResolveDice = args;
                    if (argsResolveDice.isInHibernation) {
                        this.addActionButton('stayInHibernation_button', _("Stay in Hibernation"), function () { return _this.stayInHibernation(); });
                        if (argsResolveDice.canLeaveHibernation) {
                            this.addActionButton('leaveHibernation_button', _("Leave Hibernation"), function () { return _this.leaveHibernation(); }, null, null, 'red');
                        }
                    }
                    break;
                case 'prepareResolveDice':
                    var argsPrepareResolveDice = args;
                    if (argsPrepareResolveDice.hasEncasedInIce) {
                        this.statusBar.addActionButton(_("Skip"), function () { return _this.skipFreezeDie(); });
                    }
                    break;
                case 'beforeResolveDice':
                    this.statusBar.addActionButton(_("Skip"), function () { return _this.skipBeforeResolveDice(); });
                    break;
                case 'takeWickednessTile':
                    var argsTakeWickednessTile = args;
                    this.statusBar.addActionButton(_("Skip"), function () { return _this.skipTakeWickednessTile(); }, { autoclick: !argsTakeWickednessTile.canTake && this.getGameUserPreference(202) != 2 });
                    break;
                case 'leaveTokyo':
                    var label = _("Stay in Tokyo");
                    var argsLeaveTokyo = args;
                    if (argsLeaveTokyo.canUseChestThumping && argsLeaveTokyo.activePlayerId == this.getPlayerId()) {
                        if (!this.smashedPlayersStillInTokyo) {
                            this.smashedPlayersStillInTokyo = argsLeaveTokyo.smashedPlayersInTokyo;
                        }
                        this.smashedPlayersStillInTokyo.forEach(function (playerId) {
                            var player = _this.gamedatas.players[playerId];
                            _this.addActionButton("useChestThumping_button".concat(playerId), dojo.string.substitute(_("Force ${player_name} to Yield Tokyo"), { 'player_name': "<span style=\"color: #".concat(player.color, "\">").concat(player.name, "</span>") }), function () { return _this.useChestThumping(playerId); });
                        });
                        if (this.smashedPlayersStillInTokyo.length) {
                            this.addActionButton('skipChestThumping_button', dojo.string.substitute(_("Don't use ${card_name}"), { 'card_name': this.evolutionCardsManager.getCardName(45, 'text-only') }), function () { return _this.skipChestThumping(); });
                        }
                    }
                    else {
                        var playerHasJets_1 = (_a = argsLeaveTokyo.jetsPlayers) === null || _a === void 0 ? void 0 : _a.includes(this.getPlayerId());
                        var playerHasSimianScamper = (_b = argsLeaveTokyo.simianScamperPlayers) === null || _b === void 0 ? void 0 : _b.includes(this.getPlayerId());
                        if (playerHasJets_1 || playerHasSimianScamper) {
                            label += formatTextIcons(" (- ".concat(argsLeaveTokyo.jetsDamage, " [heart])"));
                        }
                        this.addActionButton('stayInTokyo_button', label, function () { return _this.onStayInTokyo(); });
                        this.addActionButton('leaveTokyo_button', _("Leave Tokyo"), function () { return _this.onLeaveTokyo(playerHasJets_1 ? 24 : undefined); });
                        if (playerHasSimianScamper) {
                            this.addActionButton('leaveTokyoSimianScamper_button', _("Leave Tokyo") + ' : ' + dojo.string.substitute(_('Use ${card_name}'), { card_name: this.evolutionCardsManager.getCardName(42, 'text-only') }), function () { return _this.onLeaveTokyo(3042); });
                        }
                        if (!argsLeaveTokyo.canYieldTokyo[this.getPlayerId()]) {
                            this.startActionTimer('stayInTokyo_button', ACTION_TIMER_DURATION);
                            dojo.addClass('leaveTokyo_button', 'disabled');
                        }
                    }
                    break;
                case 'stealCostumeCard':
                    var argsStealCostumeCard = args;
                    this.addActionButton('endStealCostume_button', _("Skip"), function () { return _this.endStealCostume(); }, null, null, 'red');
                    if (!argsStealCostumeCard.canBuyFromPlayers && !argsStealCostumeCard.canGiveGift) {
                        this.startActionTimer('endStealCostume_button', ACTION_TIMER_DURATION);
                    }
                    break;
                case 'changeForm':
                    var argsChangeForm = args;
                    this.addActionButton('changeForm_button', dojo.string.substitute(_("Change to ${otherForm}"), { 'otherForm': _(argsChangeForm.otherForm) }) + formatTextIcons(" ( 1 [Energy])"), function () { return _this.changeForm(); });
                    this.addActionButton('skipChangeForm_button', _("Don't change form"), function () { return _this.skipChangeForm(); });
                    dojo.toggleClass('changeForm_button', 'disabled', !argsChangeForm.canChangeForm);
                    document.getElementById("changeForm_button").dataset.enableAtEnergy = '1';
                    break;
                case 'leaveTokyoExchangeCard':
                    var argsExchangeCard = args;
                    this.addActionButton('skipExchangeCard_button', _("Skip"), function () { return _this.skipExchangeCard(); });
                    if (!argsExchangeCard.canExchange) {
                        this.startActionTimer('skipExchangeCard_button', ACTION_TIMER_DURATION);
                    }
                    this.onEnteringExchangeCard(args, true); // because it's multiplayer, enter action must be set here
                    break;
                case 'beforeEnteringTokyo':
                    var argsBeforeEnteringTokyo = args;
                    if (argsBeforeEnteringTokyo.canUseFelineMotor.includes(this.getPlayerId())) {
                        this.addActionButton('useFelineMotor_button', dojo.string.substitute(_('Use ${card_name}'), { card_name: this.evolutionCardsManager.getCardName(36, 'text-only') }), function () { return _this.useFelineMotor(); });
                    }
                    this.addActionButton('skipBeforeEnteringTokyo_button', _("Skip"), function () { return _this.skipBeforeEnteringTokyo(); });
                    break;
                case 'afterEnteringTokyo':
                    this.addActionButton('skipAfterEnteringTokyo_button', _("Skip"), function () { return _this.skipAfterEnteringTokyo(); });
                    break;
                case 'buyCard':
                    var argsBuyCard = args;
                    if (argsBuyCard.canUseMiraculousCatch) {
                        this.addActionButton('useMiraculousCatch_button', dojo.string.substitute(_("Use ${card_name}"), { 'card_name': this.evolutionCardsManager.getCardName(12, 'text-only') }), function () { return _this.useMiraculousCatch(); });
                        if (!argsBuyCard.unusedMiraculousCatch) {
                            dojo.addClass('useMiraculousCatch_button', 'disabled');
                        }
                    }
                    var discardCards_1 = (_c = args._private) === null || _c === void 0 ? void 0 : _c.discardCards;
                    if (discardCards_1) {
                        var label_1 = dojo.string.substitute(_("Use ${card_name}"), { 'card_name': this.cardsManager.getCardName(64, 'text-only') });
                        if (!discardCards_1.length) {
                            label_1 += " (".concat(/*_TODOORI*/ ('discard is empty'), ")");
                        }
                        this.addActionButton('useScavenger_button', label_1, function () { return _this.showDiscardCards(discardCards_1, args); });
                        if (!discardCards_1.length) {
                            dojo.addClass('useScavenger_button', 'disabled');
                        }
                    }
                    if (argsBuyCard.canUseAdaptingTechnology) {
                        this.addActionButton('renewAdaptiveTechnology_button', _("Renew cards") + ' (' + dojo.string.substitute(_("Use ${card_name}"), { 'card_name': this.evolutionCardsManager.getCardName(24, 'text-only') }) + ')', function () { return _this.onRenew(3024); });
                    }
                    this.addActionButton('renew_button', _("Renew cards") + formatTextIcons(" ( 2 [Energy])"), function () { return _this.onRenew(4); });
                    document.getElementById('renew_button').dataset.enableAtEnergy = '2';
                    if (this.energyCounters[this.getPlayerId()].getValue() < 2) {
                        dojo.addClass('renew_button', 'disabled');
                    }
                    if (argsBuyCard.canSell) {
                        this.addActionButton('goToSellCard_button', _("End turn and sell cards"), 'goToSellCard');
                    }
                    this.addActionButton('endTurn_button', argsBuyCard.canSell ? _("End turn without selling") : _("End turn"), 'onEndTurn', null, null, 'red');
                    if (!argsBuyCard.canBuyOrNenew && !argsBuyCard.canSell) {
                        this.startActionTimer('endTurn_button', ACTION_TIMER_DURATION);
                    }
                    break;
                case 'opportunistBuyCard':
                    this.addActionButton('opportunistSkip_button', _("Skip"), 'opportunistSkip');
                    if (!args.canBuy) {
                        this.startActionTimer('opportunistSkip_button', ACTION_TIMER_DURATION);
                    }
                    this.onEnteringBuyCard(args, true); // because it's multiplayer, enter action must be set here
                    break;
                case 'opportunistChooseMimicCard':
                    this.onEnteringChooseMimickedCard(args); // because it's multiplayer, enter action must be set here
                    break;
                case 'cardIsBought':
                    this.addActionButton('skipCardIsBought_button', _("Skip"), function () { return _this.skipCardIsBought(); });
                    break;
                case 'sellCard':
                    this.addActionButton('endTurnSellCard_button', _("End turn"), 'onEndTurn', null, null, 'red');
                    break;
                case 'answerQuestion':
                    this.onUpdateActionButtonsAnswerQuestion(args);
            }
        }
    };
    KingOfTokyo.prototype.onUpdateActionButtonsAnswerQuestion = function (args) {
        var _this = this;
        var question = args.question;
        switch (question.code) {
            case 'BambooSupply':
                var substituteParams = { card_name: this.evolutionCardsManager.getCardName(136, 'text-only') };
                var putLabel = dojo.string.substitute(_("Put ${number}[Energy] on ${card_name}"), __assign(__assign({}, substituteParams), { number: 1 }));
                var takeLabel = dojo.string.substitute(_("Take all [Energy] from ${card_name}"), substituteParams);
                this.addActionButton('putEnergyOnBambooSupply_button', formatTextIcons(putLabel), function () { return _this.putEnergyOnBambooSupply(); });
                this.addActionButton('takeEnergyOnBambooSupply_button', formatTextIcons(takeLabel), function () { return _this.takeEnergyOnBambooSupply(); });
                var bambooSupplyQuestionArgs = question.args;
                if (!bambooSupplyQuestionArgs.canTake) {
                    dojo.addClass('takeEnergyOnBambooSupply_button', 'disabled');
                }
                break;
            case 'GazeOfTheSphinxAnkh':
                this.addActionButton('gazeOfTheSphinxDrawEvolution_button', _("Draw Evolution"), function () { return _this.gazeOfTheSphinxDrawEvolution(); });
                this.addActionButton('gazeOfTheSphinxGainEnergy_button', formatTextIcons("".concat(dojo.string.substitute(_('Gain ${energy}[Energy]'), { energy: 3 }))), function () { return _this.gazeOfTheSphinxGainEnergy(); });
                break;
            case 'GazeOfTheSphinxSnake':
                this.addActionButton('gazeOfTheSphinxLoseEnergy_button', formatTextIcons("".concat(dojo.string.substitute(_('Lose ${energy}[Energy]'), { energy: 3 }))), function () { return _this.gazeOfTheSphinxLoseEnergy(); });
                var gazeOfTheSphinxLoseEnergyQuestionArgs = question.args;
                if (!gazeOfTheSphinxLoseEnergyQuestionArgs.canLoseEnergy) {
                    dojo.addClass('gazeOfTheSphinxLoseEnergy_button', 'disabled');
                }
                break;
            case 'GiveSymbol':
                var giveSymbolPlayerId_1 = this.getPlayerId();
                var giveSymbolQuestionArgs = question.args;
                giveSymbolQuestionArgs.symbols.forEach(function (symbol) {
                    _this.addActionButton("giveSymbol_button".concat(symbol), formatTextIcons(dojo.string.substitute(_("Give ${symbol}"), { symbol: SYMBOL_AS_STRING_PADDED[symbol] })), function () { return _this.giveSymbol(symbol); });
                    if (!question.args["canGive".concat(symbol)].includes(giveSymbolPlayerId_1)) {
                        dojo.addClass("giveSymbol_button".concat(symbol), 'disabled');
                    }
                    if (symbol == 5) {
                        var giveEnergyButton_1 = document.getElementById("giveSymbol_button5");
                        giveEnergyButton_1.dataset.enableAtEnergy = '1';
                        _this.updateEnableAtEnergy(_this.getPlayerId());
                    }
                });
                break;
            case 'GiveEnergyOrLoseHearts':
                var giveEnergyOrLoseHeartsPlayerId = this.getPlayerId();
                var giveEnergyOrLoseHeartsQuestionArgs = question.args;
                this.addActionButton("giveSymbol_button5", formatTextIcons(dojo.string.substitute(_("Give ${symbol}"), { symbol: SYMBOL_AS_STRING_PADDED[5] })), function () { return _this.giveSymbol(5); });
                var giveEnergyButton = document.getElementById("giveSymbol_button5");
                giveEnergyButton.dataset.enableAtEnergy = '1';
                this.updateEnableAtEnergy(this.getPlayerId());
                if (!giveEnergyOrLoseHeartsQuestionArgs.canGiveEnergy.includes(giveEnergyOrLoseHeartsPlayerId)) {
                    giveEnergyButton.classList.add('disabled');
                }
                this.addActionButton("loseHearts_button", formatTextIcons(dojo.string.substitute(_("Lose ${symbol}"), { symbol: "".concat(giveEnergyOrLoseHeartsQuestionArgs.heartNumber, "[Heart]") })), function () { return _this.loseHearts(); });
                break;
            case 'FreezeRay':
                var _loop_7 = function (face) {
                    this_4.addActionButton("selectFrozenDieFace_button".concat(face), formatTextIcons(DICE_STRINGS[face]), function () { return _this.chooseFreezeRayDieFace(face); });
                };
                var this_4 = this;
                for (var face = 1; face <= 6; face++) {
                    _loop_7(face);
                }
                break;
            case 'MiraculousCatch':
                var miraculousCatchArgs = question.args;
                this.addActionButton('buyCardMiraculousCatch_button', formatTextIcons(dojo.string.substitute(_('Buy ${card_name} for ${cost}[Energy]'), { card_name: this.cardsManager.getCardName(miraculousCatchArgs.card.type, 'text-only'), cost: miraculousCatchArgs.cost })), function () { return _this.buyCardMiraculousCatch(false); });
                if (miraculousCatchArgs.costSuperiorAlienTechnology !== null && miraculousCatchArgs.costSuperiorAlienTechnology !== miraculousCatchArgs.cost) {
                    this.addActionButton('buyCardMiraculousCatchUseSuperiorAlienTechnology_button', formatTextIcons(dojo.string.substitute(_('Use ${card_name} and pay half cost ${cost}[Energy]'), { card_name: this.evolutionCardsManager.getCardName(28, 'text-only'), cost: miraculousCatchArgs.costSuperiorAlienTechnology })), function () { return _this.buyCardMiraculousCatch(true); });
                }
                this.addActionButton('skipMiraculousCatch_button', formatTextIcons(dojo.string.substitute(_('Discard ${card_name}'), { card_name: this.cardsManager.getCardName(miraculousCatchArgs.card.type, 'text-only') })), function () { return _this.skipMiraculousCatch(); });
                document.getElementById('buyCardMiraculousCatch_button').dataset.enableAtEnergy = '' + miraculousCatchArgs.cost;
                dojo.toggleClass('buyCardMiraculousCatch_button', 'disabled', this.getPlayerEnergy(this.getPlayerId()) < miraculousCatchArgs.cost);
                break;
            case 'DeepDive':
                var deepDiveCatchArgs = question.args;
                deepDiveCatchArgs.cards.forEach(function (card) {
                    _this.addActionButton("playCardDeepDive_button".concat(card.id), formatTextIcons(dojo.string.substitute(_('Play ${card_name}'), { card_name: _this.cardsManager.getCardName(card.type, 'text-only') })), function () { return _this.playCardDeepDive(card.id); });
                });
                break;
            case 'ExoticArms':
                var useExoticArmsLabel = dojo.string.substitute(_("Put ${number}[Energy] on ${card_name}"), { card_name: this.evolutionCardsManager.getCardName(26, 'text-only'), number: 2 });
                this.addActionButton('useExoticArms_button', formatTextIcons(useExoticArmsLabel), function () { return _this.useExoticArms(); });
                this.addActionButton('skipExoticArms_button', _('Skip'), function () { return _this.skipExoticArms(); });
                dojo.toggleClass('useExoticArms_button', 'disabled', this.getPlayerEnergy(this.getPlayerId()) < 2);
                document.getElementById('useExoticArms_button').dataset.enableAtEnergy = '2';
                break;
            case 'TargetAcquired':
                var targetAcquiredCatchArgs = question.args;
                this.addActionButton('giveTarget_button', dojo.string.substitute(_("Give target to ${player_name}"), { 'player_name': this.getPlayer(targetAcquiredCatchArgs.playerId).name }), function () { return _this.giveTarget(); });
                this.addActionButton('skipGiveTarget_button', _('Skip'), function () { return _this.skipGiveTarget(); });
                break;
            case 'LightningArmor':
                this.addActionButton('useLightningArmor_button', _("Throw dice"), function () { return _this.useLightningArmor(); });
                this.addActionButton('skipLightningArmor_button', _('Skip'), function () { return _this.skipLightningArmor(); });
                break;
            case 'EnergySword':
                this.addActionButton('useEnergySword_button', dojo.string.substitute(_("Use ${card_name}"), { card_name: this.evolutionCardsManager.getCardName(147, 'text-only') }), function () { return _this.answerEnergySword(true); });
                this.addActionButton('skipEnergySword_button', _('Skip'), function () { return _this.answerEnergySword(false); });
                dojo.toggleClass('useEnergySword_button', 'disabled', this.getPlayerEnergy(this.getPlayerId()) < 2);
                document.getElementById('useEnergySword_button').dataset.enableAtEnergy = '2';
                break;
            case 'SunkenTemple':
                this.addActionButton('useSunkenTemple_button', dojo.string.substitute(_("Use ${card_name}"), { card_name: this.evolutionCardsManager.getCardName(157, 'text-only') }), function () { return _this.answerSunkenTemple(true); });
                this.addActionButton('skipSunkenTemple_button', _('Skip'), function () { return _this.answerSunkenTemple(false); });
                break;
            case 'ElectricCarrot':
                this.addActionButton('answerElectricCarrot5_button', formatTextIcons(dojo.string.substitute(_("Give ${symbol}"), { symbol: '[Energy]' })), function () { return _this.answerElectricCarrot(5); });
                dojo.toggleClass('answerElectricCarrot5_button', 'disabled', this.getPlayerEnergy(this.getPlayerId()) < 1);
                document.getElementById('answerElectricCarrot5_button').dataset.enableAtEnergy = '1';
                this.addActionButton('answerElectricCarrot4_button', formatTextIcons(_("Lose 1 extra [Heart]")), function () { return _this.answerElectricCarrot(4); });
                break;
            case 'SuperiorAlienTechnology':
                this.addActionButton('throwDieSuperiorAlienTechnology_button', _('Roll a die'), function () { return _this.throwDieSuperiorAlienTechnology(); });
                break;
        }
    };
    ///////////////////////////////////////////////////
    //// Utility methods
    ///////////////////////////////////////////////////
    KingOfTokyo.prototype.getPlayerId = function () {
        return Number(this.player_id);
    };
    KingOfTokyo.prototype.isHalloweenExpansion = function () {
        return this.gamedatas.halloweenExpansion;
    };
    KingOfTokyo.prototype.isKingkongExpansion = function () {
        return this.gamedatas.kingkongExpansion;
    };
    KingOfTokyo.prototype.isCybertoothExpansion = function () {
        return this.gamedatas.cybertoothExpansion;
    };
    KingOfTokyo.prototype.isMutantEvolutionVariant = function () {
        return this.gamedatas.mutantEvolutionVariant;
    };
    KingOfTokyo.prototype.isCthulhuExpansion = function () {
        return this.gamedatas.cthulhuExpansion;
    };
    KingOfTokyo.prototype.isAnubisExpansion = function () {
        return this.gamedatas.anubisExpansion;
    };
    KingOfTokyo.prototype.isWickednessExpansion = function () {
        return this.gamedatas.wickednessExpansion;
    };
    KingOfTokyo.prototype.isPowerUpExpansion = function () {
        return this.gamedatas.powerUpExpansion;
    };
    KingOfTokyo.prototype.isOrigins = function () {
        return this.gamedatas.origins;
    };
    KingOfTokyo.prototype.isDarkEdition = function () {
        return this.gamedatas.darkEdition;
    };
    KingOfTokyo.prototype.isDefaultFont = function () {
        return this.getGameUserPreference(201) == 1;
    };
    KingOfTokyo.prototype.getPlayer = function (playerId) {
        return this.gamedatas.players[playerId];
    };
    KingOfTokyo.prototype.createButton = function (destinationId, id, text, callback, disabled, dojoPlace) {
        if (disabled === void 0) { disabled = false; }
        if (dojoPlace === void 0) { dojoPlace = undefined; }
        return this.statusBar.addActionButton(text, callback, {
            id: id,
            classes: disabled ? 'disabled' : '',
            destination: $(destinationId),
        });
    };
    KingOfTokyo.prototype.addTwoPlayerVariantNotice = function (gamedatas) {
        var _this = this;
        // 2-players variant notice
        if (Object.keys(gamedatas.players).length == 2 && this.getGameUserPreference(203) == 1) {
            dojo.place("\n                    <div id=\"board-corner-highlight\"></div>\n                    <div id=\"twoPlayersVariant-message\">\n                        ".concat(_("You are playing the 2-players variant."), "<br>\n                        ").concat(_("When entering or starting a turn on Tokyo, you gain 1 energy instead of points"), ".<br>\n                        ").concat(_("You can check if variant is activated in the bottom left corner of the table."), "<br>\n                        <div style=\"text-align: center\"><a id=\"hide-twoPlayersVariant-message\">").concat(_("Dismiss"), "</a></div>\n                    </div>\n                "), 'board');
            document.getElementById('hide-twoPlayersVariant-message').addEventListener('click', function () { return _this.setGameUserPreference(203, 2); });
        }
    };
    KingOfTokyo.prototype.getOrderedPlayers = function () {
        return Object.values(this.gamedatas.players).sort(function (a, b) { return Number(a.player_no) - Number(b.player_no); });
    };
    KingOfTokyo.prototype.createPlayerPanels = function (gamedatas) {
        var _this = this;
        Object.values(gamedatas.players).forEach(function (player) {
            var playerId = Number(player.id);
            var eliminated = Number(player.eliminated) > 0 || player.playerDead > 0;
            // health & energy counters
            var html = "<div class=\"counters\">\n                <div id=\"health-counter-wrapper-".concat(player.id, "\" class=\"counter\">\n                    <div class=\"icon health\"></div> \n                    <span id=\"health-counter-").concat(player.id, "\"></span>\n                </div>\n                <div id=\"energy-counter-wrapper-").concat(player.id, "\" class=\"counter\">\n                    <div class=\"icon energy\"></div> \n                    <span id=\"energy-counter-").concat(player.id, "\"></span>\n                </div>");
            if (gamedatas.wickednessExpansion) {
                html += "\n                <div id=\"wickedness-counter-wrapper-".concat(player.id, "\" class=\"counter\">\n                    <div class=\"icon wickedness\"></div> \n                    <span id=\"wickedness-counter-").concat(player.id, "\"></span>\n                </div>");
            }
            html += "</div>";
            dojo.place(html, "player_board_".concat(player.id));
            _this.addTooltipHtml("health-counter-wrapper-".concat(player.id), _("Health"));
            _this.addTooltipHtml("energy-counter-wrapper-".concat(player.id), _("Energy"));
            if (gamedatas.wickednessExpansion) {
                _this.addTooltipHtml("wickedness-counter-wrapper-".concat(player.id), _("Wickedness points"));
            }
            if (gamedatas.kingkongExpansion || gamedatas.cybertoothExpansion || gamedatas.cthulhuExpansion) {
                var html_1 = "<div class=\"counters\">";
                if (gamedatas.cthulhuExpansion) {
                    html_1 += "\n                    <div id=\"cultist-counter-wrapper-".concat(player.id, "\" class=\"counter cultist-tooltip\">\n                        <div class=\"icon cultist\"></div>\n                        <span id=\"cultist-counter-").concat(player.id, "\"></span>\n                    </div>");
                }
                if (gamedatas.kingkongExpansion) {
                    html_1 += "<div id=\"tokyo-tower-counter-wrapper-".concat(player.id, "\" class=\"counter tokyo-tower-tooltip\">");
                    for (var level = 1; level <= 3; level++) {
                        html_1 += "<div id=\"tokyo-tower-icon-".concat(player.id, "-level-").concat(level, "\" class=\"tokyo-tower-icon level").concat(level, "\" data-owned=\"").concat(player.tokyoTowerLevels.includes(level).toString(), "\"></div>");
                    }
                    html_1 += "</div>";
                }
                if (gamedatas.cybertoothExpansion) {
                    html_1 += "\n                    <div id=\"berserk-counter-wrapper-".concat(player.id, "\" class=\"counter berserk-tooltip\">\n                        <div class=\"berserk-icon-wrapper\">\n                            <div id=\"player-panel-berserk-").concat(player.id, "\" class=\"berserk icon ").concat(player.berserk ? 'active' : '', "\"></div>\n                        </div>\n                    </div>");
                }
                html_1 += "</div>";
                dojo.place(html_1, "player_board_".concat(player.id));
                if (gamedatas.cthulhuExpansion) {
                    var cultistCounter = new ebg.counter();
                    cultistCounter.create("cultist-counter-".concat(player.id));
                    cultistCounter.setValue(player.cultists);
                    _this.cultistCounters[playerId] = cultistCounter;
                }
            }
            var healthCounter = new ebg.counter();
            healthCounter.create("health-counter-".concat(player.id));
            healthCounter.setValue(player.health);
            _this.healthCounters[playerId] = healthCounter;
            var energyCounter = new ebg.counter();
            energyCounter.create("energy-counter-".concat(player.id));
            energyCounter.setValue(player.energy);
            _this.energyCounters[playerId] = energyCounter;
            if (gamedatas.wickednessExpansion) {
                var wickednessCounter = new ebg.counter();
                wickednessCounter.create("wickedness-counter-".concat(player.id));
                wickednessCounter.setValue(player.wickedness);
                _this.wickednessCounters[playerId] = wickednessCounter;
            }
            if (gamedatas.powerUpExpansion) {
                // hand cards counter
                dojo.place("<div class=\"counters\">\n                    <div id=\"playerhand-counter-wrapper-".concat(player.id, "\" class=\"playerhand-counter\">\n                        <div class=\"player-evolution-card\"></div>\n                        <div class=\"player-hand-card\"></div> \n                        <span id=\"playerhand-counter-").concat(player.id, "\"></span>\n                    </div>\n                    <div class=\"show-evolutions-button\">\n                    <button id=\"see-monster-evolution-player-").concat(playerId, "\" class=\"bgabutton bgabutton_gray ").concat(Number(_this.gamedatas.gamestate.id) >= 15 /*ST_PLAYER_CHOOSE_INITIAL_CARD*/ ? 'visible' : '', "\">\n                        ").concat(_('Show Evolutions'), "\n                    </button>\n                    </div>\n                </div>"), "player_board_".concat(player.id));
                var handCounter = new ebg.counter();
                handCounter.create("playerhand-counter-".concat(playerId));
                handCounter.setValue(player.hiddenEvolutions.length);
                _this.handCounters[playerId] = handCounter;
                _this.addTooltipHtml("playerhand-counter-wrapper-".concat(player.id), _("Number of Evolution cards in hand."));
                document.getElementById("see-monster-evolution-player-".concat(playerId)).addEventListener('click', function () { return _this.showPlayerEvolutions(playerId); });
            }
            dojo.place("<div class=\"player-tokens\">\n                <div id=\"player-board-target-tokens-".concat(player.id, "\" class=\"player-token target-tokens\"></div>\n                <div id=\"player-board-shrink-ray-tokens-").concat(player.id, "\" class=\"player-token shrink-ray-tokens\"></div>\n                <div id=\"player-board-poison-tokens-").concat(player.id, "\" class=\"player-token poison-tokens\"></div>\n                <div id=\"player-board-mindbug-tokens-").concat(player.id, "\" class=\"player-token mindbug-tokens\"></div>\n            </div>"), "player_board_".concat(player.id));
            if (!eliminated) {
                _this.setShrinkRayTokens(playerId, player.shrinkRayTokens);
                _this.setPoisonTokens(playerId, player.poisonTokens);
                _this.setPlayerTokens(playerId, gamedatas.targetedPlayer == playerId ? 1 : 0, 'target');
                _this.setPlayerTokens(playerId, player.mindbugTokens, 'mindbug');
            }
            dojo.place("<div id=\"player-board-monster-figure-".concat(player.id, "\" class=\"monster-figure monster").concat(player.monster, "\"><div class=\"kot-token\"></div></div>"), "player_board_".concat(player.id));
            if (player.location > 0) {
                dojo.addClass("overall_player_board_".concat(playerId), 'intokyo');
            }
            if (eliminated) {
                setTimeout(function () { return _this.eliminatePlayer(playerId); }, 200);
            }
        });
        this.addTooltipHtmlToClass('shrink-ray-tokens', this.SHINK_RAY_TOKEN_TOOLTIP);
        this.addTooltipHtmlToClass('poison-tokens', this.POISON_TOKEN_TOOLTIP);
    };
    KingOfTokyo.prototype.createPlayerTables = function (gamedatas) {
        var _this = this;
        var evolutionCardsWithSingleState = this.isPowerUpExpansion() ?
            Object.values(this.gamedatas.EVOLUTION_CARDS_SINGLE_STATE).reduce(function (a1, a2) { return __spreadArray(__spreadArray([], a1, true), a2, true); }, []) :
            null;
        this.playerTables = this.getOrderedPlayers().map(function (player) {
            var playerId = Number(player.id);
            var playerWithGoldenScarab = gamedatas.anubisExpansion && playerId === gamedatas.playerWithGoldenScarab;
            return new PlayerTable(_this, player, playerWithGoldenScarab, evolutionCardsWithSingleState);
        });
        if (gamedatas.targetedPlayer) {
            this.getPlayerTable(gamedatas.targetedPlayer).giveTarget();
        }
    };
    KingOfTokyo.prototype.getPlayerTable = function (playerId) {
        return this.playerTables.find(function (playerTable) { return playerTable.playerId === Number(playerId); });
    };
    KingOfTokyo.prototype.isInitialCardDoubleSelection = function () {
        var args = this.gamedatas.gamestate.args;
        return args.chooseCostume && args.chooseEvolution;
    };
    KingOfTokyo.prototype.confirmDoubleSelectionCheckState = function () {
        var _a, _b, _c;
        var costumeSelected = ((_a = this.tableCenter.getVisibleCards()) === null || _a === void 0 ? void 0 : _a.getSelection().length) === 1;
        var evolutionSelected = ((_b = this.getPlayerTable(this.getPlayerId())) === null || _b === void 0 ? void 0 : _b.pickEvolutionCards.getSelection().length) === 1;
        (_c = document.getElementById("confirmInitialCards_button")) === null || _c === void 0 ? void 0 : _c.classList.toggle('disabled', !costumeSelected || !evolutionSelected);
    };
    KingOfTokyo.prototype.setDiceSelectorVisibility = function (visible) {
        var div = document.getElementById('rolled-dice');
        div.style.display = visible ? 'flex' : 'none';
    };
    KingOfTokyo.prototype.getZoom = function () {
        return this.tableManager.zoom;
    };
    KingOfTokyo.prototype.getPreferencesManager = function () {
        return this.preferencesManager;
    };
    KingOfTokyo.prototype.removeMonsterChoice = function () {
        if (document.getElementById('monster-pick')) {
            this.fadeOutAndDestroy('monster-pick');
        }
    };
    KingOfTokyo.prototype.removeMutantEvolutionChoice = function () {
        if (document.getElementById('mutant-evolution-choice')) {
            this.fadeOutAndDestroy('mutant-evolution-choice');
        }
    };
    KingOfTokyo.prototype.showMainTable = function () {
        if (dojo.hasClass('kot-table', 'pickMonsterOrEvolutionDeck')) {
            dojo.removeClass('kot-table', 'pickMonsterOrEvolutionDeck');
            this.tableManager.setAutoZoomAndPlacePlayerTables();
        }
    };
    KingOfTokyo.prototype.getStateName = function () {
        return this.gamedatas.gamestate.name;
    };
    KingOfTokyo.prototype.toggleRerollDiceButton = function () {
        var args = this.gamedatas.gamestate.args;
        var selectedDiceCount = this.diceManager.getSelectedDiceIds().length;
        var canReroll = selectedDiceCount >= args.min && selectedDiceCount <= args.max;
        dojo.toggleClass('rerollDice_button', 'disabled', !canReroll);
    };
    KingOfTokyo.prototype.onVisibleCardClick = function (stock, card, from, warningChecked) {
        var _this = this;
        var _a, _b;
        if (from === void 0) { from = 0; }
        if (warningChecked === void 0) { warningChecked = false; }
        if (!(card === null || card === void 0 ? void 0 : card.id)) {
            return;
        }
        if (stock.getCardElement(card).classList.contains('disabled')) {
            stock.unselectCard(card);
            return;
        }
        var stateName = this.getStateName();
        if (stateName === 'chooseInitialCard') {
            if (!this.isInitialCardDoubleSelection()) {
                this.chooseInitialCard(card.id, null);
            }
            else {
                this.confirmDoubleSelectionCheckState();
            }
        }
        else if (stateName === 'stealCostumeCard') {
            this.stealCostumeCard(card.id);
        }
        else if (stateName === 'sellCard') {
            this.sellCard(card.id);
        }
        else if (stateName === 'chooseMimickedCard' || stateName === 'opportunistChooseMimicCard') {
            this.chooseMimickedCard(card.id);
        }
        else if (stateName === 'changeMimickedCard') {
            this.changeMimickedCard(card.id);
        }
        else if (stateName === 'chooseMimickedCardWickednessTile') {
            this.chooseMimickedCardWickednessTile(card.id);
        }
        else if (stateName === 'changeMimickedCardWickednessTile') {
            this.changeMimickedCardWickednessTile(card.id);
        }
        else if (stateName === 'buyCard' || stateName === 'opportunistBuyCard') {
            var buyCardArgs = this.gamedatas.gamestate.args;
            var warningIcon = !warningChecked && buyCardArgs.warningIds[card.id];
            if (!warningChecked && buyCardArgs.noExtraTurnWarning.includes(card.type)) {
                this.confirmationDialog(this.getNoExtraTurnWarningMessage(), function () { return _this.onVisibleCardClick(stock, card, from, true); });
            }
            else if (warningIcon) {
                this.confirmationDialog(formatTextIcons(dojo.string.substitute(_("Are you sure you want to buy that card? You won't gain ${symbol}"), { symbol: warningIcon })), function () { return _this.onVisibleCardClick(stock, card, from, true); });
            }
            else {
                var cardCostSuperiorAlienTechnology = (_a = buyCardArgs.cardsCostsSuperiorAlienTechnology) === null || _a === void 0 ? void 0 : _a[card.id];
                var cardCostBobbingForApples = (_b = buyCardArgs.cardsCostsBobbingForApples) === null || _b === void 0 ? void 0 : _b[card.id];
                var canUseSuperiorAlienTechnologyForCard_1 = cardCostSuperiorAlienTechnology !== null && cardCostSuperiorAlienTechnology !== undefined && cardCostSuperiorAlienTechnology !== buyCardArgs.cardsCosts[card.id];
                var canUseBobbingForApplesForCard_1 = cardCostBobbingForApples !== null && cardCostBobbingForApples !== undefined && cardCostBobbingForApples !== buyCardArgs.cardsCosts[card.id];
                if (canUseSuperiorAlienTechnologyForCard_1 || canUseBobbingForApplesForCard_1) {
                    var both_1 = canUseSuperiorAlienTechnologyForCard_1 && canUseBobbingForApplesForCard_1;
                    var keys = [
                        formatTextIcons(dojo.string.substitute(_('Don\'t use ${card_name} and pay full cost ${cost}[Energy]'), { card_name: this.evolutionCardsManager.getCardName(canUseSuperiorAlienTechnologyForCard_1 ? 28 : 85, 'text-only'), cost: buyCardArgs.cardsCosts[card.id] })),
                        _('Cancel')
                    ];
                    if (cardCostBobbingForApples) {
                        keys.unshift(formatTextIcons(dojo.string.substitute(_('Use ${card_name} and pay ${cost}[Energy]'), { card_name: this.evolutionCardsManager.getCardName(85, 'text-only'), cost: cardCostBobbingForApples })));
                    }
                    if (canUseSuperiorAlienTechnologyForCard_1) {
                        keys.unshift(formatTextIcons(dojo.string.substitute(_('Use ${card_name} and pay half cost ${cost}[Energy]'), { card_name: this.evolutionCardsManager.getCardName(28, 'text-only'), cost: cardCostSuperiorAlienTechnology })));
                    }
                    this.multipleChoiceDialog(dojo.string.substitute(_('Do you want to buy the card at reduced cost with ${card_name} ?'), { 'card_name': this.evolutionCardsManager.getCardName(28, 'text-only') }), keys, function (choice) {
                        var choiceIndex = Number(choice);
                        if (choiceIndex < (both_1 ? 3 : 2)) {
                            _this.tableCenter.removeOtherCardsFromPick(card.id);
                            _this.buyCard(card.id, from, canUseSuperiorAlienTechnologyForCard_1 && choiceIndex === 0, canUseBobbingForApplesForCard_1 && choiceIndex === (both_1 ? 1 : 0));
                        }
                    });
                    if (canUseSuperiorAlienTechnologyForCard_1 && buyCardArgs.canUseSuperiorAlienTechnology === false || cardCostSuperiorAlienTechnology > this.getPlayerEnergy(this.getPlayerId())) {
                        document.getElementById("choice_btn_0").classList.add('disabled');
                    }
                    if (canUseBobbingForApplesForCard_1 && cardCostBobbingForApples > this.getPlayerEnergy(this.getPlayerId())) {
                        document.getElementById("choice_btn_".concat((both_1 ? 1 : 0))).classList.add('disabled');
                    }
                    if (buyCardArgs.cardsCosts[card.id] > this.getPlayerEnergy(this.getPlayerId())) {
                        document.getElementById("choice_btn_".concat((both_1 ? 2 : 1))).classList.add('disabled');
                    }
                }
                else {
                    this.tableCenter.removeOtherCardsFromPick(card.id);
                    this.buyCard(card.id, from);
                }
            }
        }
        else if (stateName === 'discardKeepCard') {
            this.discardKeepCard(card.id);
        }
        else if (stateName === 'leaveTokyoExchangeCard') {
            this.exchangeCard(card.id);
        }
        else if (stateName === 'answerQuestion') {
            var args = this.gamedatas.gamestate.args;
            if (args.question.code === 'Bamboozle') {
                this.buyCardBamboozle(card.id, from);
            }
            else if (args.question.code === 'ChooseMimickedCard') {
                this.chooseMimickedCard(card.id);
            }
            else if (args.question.code === 'MyToy') {
                this.reserveCard(card.id);
            }
        }
    };
    KingOfTokyo.prototype.chooseEvolutionCardClick = function (id) {
        var stateName = this.getStateName();
        if (stateName === 'chooseInitialCard') {
            if (!this.isInitialCardDoubleSelection()) {
                this.chooseInitialCard(null, id);
            }
            else {
                this.confirmDoubleSelectionCheckState();
            }
        }
        else if (stateName === 'chooseEvolutionCard') {
            this.chooseEvolutionCard(id);
        }
    };
    KingOfTokyo.prototype.onSelectGiftEvolution = function (cardId) {
        var _this = this;
        var generalActionButtons = Array.from(document.getElementById("generalactions").getElementsByClassName("action-button"));
        generalActionButtons = generalActionButtons.slice(0, generalActionButtons.findIndex(function (button) { return button.id == 'endStealCostume_button'; }));
        generalActionButtons.forEach(function (generalActionButton) { return generalActionButton.remove(); });
        var args = this.gamedatas.gamestate.args;
        args.woundedPlayersIds.slice().reverse().forEach(function (woundedPlayerId) {
            var woundedPlayer = _this.getPlayer(woundedPlayerId);
            var cardType = Number(document.querySelector("[data-evolution-id=\"".concat(cardId, "\"]")).dataset.evolutionType);
            var label = /*TODOPUHA_*/ ('Give ${card_name} to ${player_name}').replace('${card_name}', _this.evolutionCardsManager.getCardName(cardType, 'text-only')).replace('${player_name}', "<strong style=\"color: #".concat(woundedPlayer.color, ";\">").concat(woundedPlayer.name, "</strong>"));
            var button = _this.createButton('endStealCostume_button', "giveGift".concat(cardId, "to").concat(woundedPlayerId, "_button"), label, function () { return _this.giveGiftEvolution(cardId, woundedPlayerId); }, false, 'before');
            document.getElementById("giveGift".concat(cardId, "to").concat(woundedPlayerId, "_button")).insertAdjacentElement('beforebegin', button);
        });
    };
    KingOfTokyo.prototype.onHiddenEvolutionClick = function (card) {
        var _this = this;
        var _a;
        var stateName = this.getStateName();
        if (stateName === 'answerQuestion') {
            var args_2 = this.gamedatas.gamestate.args;
            if (args_2.question.code === 'GazeOfTheSphinxSnake') {
                this.gazeOfTheSphinxDiscardEvolution(Number(card.id));
                this.gazeOfTheSphinxDiscardEvolution(Number(card.id));
                return;
            }
        }
        else if (stateName === 'stealCostumeCard') {
            this.onSelectGiftEvolution(card.id);
            this.onSelectGiftEvolution(card.id);
            return;
        }
        var args = this.gamedatas.gamestate.args;
        if ((_a = args.noExtraTurnWarning) === null || _a === void 0 ? void 0 : _a.includes(card.type)) {
            this.confirmationDialog(this.getNoExtraTurnWarningMessage(), function () { return _this.playEvolution(card.id); });
        }
        else {
            this.playEvolution(card.id);
        }
    };
    KingOfTokyo.prototype.onVisibleEvolutionClick = function (cardId) {
        var stateName = this.getStateName();
        if (stateName === 'answerQuestion') {
            var args = this.gamedatas.gamestate.args;
            if (args.question.code === 'GazeOfTheSphinxSnake') {
                this.gazeOfTheSphinxDiscardEvolution(Number(cardId));
            }
            else if (args.question.code === 'IcyReflection') {
                this.chooseMimickedEvolution(Number(cardId));
            }
        }
        else if (stateName === 'stealCostumeCard') {
            this.onSelectGiftEvolution(cardId);
        }
    };
    KingOfTokyo.prototype.setBuyDisabledCardByCost = function (disabledIds, cardsCosts, playerEnergy) {
        this.setBuyDisabledCardByCostForStock(disabledIds, cardsCosts, playerEnergy, this.tableCenter.getVisibleCards());
    };
    KingOfTokyo.prototype.setBuyDisabledCardByCostForStock = function (disabledIds, cardsCosts, playerEnergy, stock) {
        var _this = this;
        var disabledCardsIds = __spreadArray(__spreadArray([], disabledIds, true), Object.keys(cardsCosts).map(function (cardId) { return Number(cardId); }), true);
        disabledCardsIds.forEach(function (id) {
            var disabled = disabledIds.some(function (disabledId) { return disabledId == id; }) || cardsCosts[id] > playerEnergy;
            var cardDiv = _this.cardsManager.getCardElement({ id: id });
            cardDiv === null || cardDiv === void 0 ? void 0 : cardDiv.classList.toggle('bga-cards_disabled-card', disabled);
        });
        var selectableCards = stock.getCards().filter(function (card) {
            var disabled = disabledIds.some(function (disabledId) { return disabledId == card.id; }) || cardsCosts[card.id] > playerEnergy;
            return !disabled;
        });
        stock.setSelectableCards(selectableCards);
    };
    KingOfTokyo.prototype.getCardCosts = function (args) {
        var cardsCosts = __assign({}, args.cardsCosts);
        var argsBuyCard = args;
        if (argsBuyCard.gotSuperiorAlienTechnology) {
            cardsCosts = __assign(__assign({}, cardsCosts), argsBuyCard.cardsCostsSuperiorAlienTechnology);
        }
        if (argsBuyCard.cardsCostsBobbingForApples) {
            Object.keys(argsBuyCard.cardsCostsBobbingForApples).forEach(function (cardId) {
                if (argsBuyCard.cardsCostsBobbingForApples[cardId] < cardsCosts[cardId]) {
                    cardsCosts[cardId] = argsBuyCard.cardsCostsBobbingForApples[cardId];
                }
            });
        }
        return cardsCosts;
    };
    // called on state enter and when energy number is changed
    KingOfTokyo.prototype.setBuyDisabledCard = function (args, playerEnergy) {
        if (args === void 0) { args = null; }
        if (playerEnergy === void 0) { playerEnergy = null; }
        if (!this.isCurrentPlayerActive()) {
            return;
        }
        var stateName = this.getStateName();
        var buyState = stateName === 'buyCard' || stateName === 'opportunistBuyCard' || stateName === 'stealCostumeCard' || (stateName === 'answerQuestion' && ['ChooseMimickedCard', 'Bamboozle'].includes(this.gamedatas.gamestate.args.question.code));
        var changeMimicState = stateName === 'changeMimickedCard' || stateName === 'changeMimickedCardWickednessTile';
        if (!buyState && !changeMimicState) {
            return;
        }
        var bamboozle = stateName === 'answerQuestion' && this.gamedatas.gamestate.args.question.code === 'Bamboozle';
        var playerId = this.getPlayerId();
        if (bamboozle) {
            playerId = this.gamedatas.gamestate.args.question.args.cardBeingBought.playerId;
            playerEnergy = this.energyCounters[playerId].getValue();
        }
        if (args === null) {
            args = bamboozle ?
                this.gamedatas.gamestate.args.question.args.buyCardArgs :
                this.gamedatas.gamestate.args;
        }
        if (playerEnergy === null) {
            playerEnergy = this.energyCounters[playerId].getValue();
        }
        var cardsCosts = this.getCardCosts(args);
        this.setBuyDisabledCardByCost(args.disabledIds, cardsCosts, playerEnergy);
        // renew button
        if (buyState && document.getElementById('renew_button')) {
            dojo.toggleClass('renew_button', 'disabled', playerEnergy < 2);
        }
    };
    KingOfTokyo.prototype.addRapidHealingButton = function (userEnergy, isMaxHealth) {
        var _this = this;
        if (!document.getElementById('rapidHealingButton')) {
            this.createButton('rapid-actions-wrapper', 'rapidHealingButton', dojo.string.substitute(_("Use ${card_name}") + " : " + formatTextIcons("".concat(_('Gain ${hearts}[Heart]'), " (2[Energy])")), { card_name: this.cardsManager.getCardName(37, 'text-only'), hearts: 1 }), function () { return _this.useRapidHealing(); }, userEnergy < 2 || isMaxHealth);
        }
    };
    KingOfTokyo.prototype.removeRapidHealingButton = function () {
        if (document.getElementById('rapidHealingButton')) {
            dojo.destroy('rapidHealingButton');
        }
    };
    KingOfTokyo.prototype.addMothershipSupportButton = function (userEnergy, isMaxHealth) {
        var _this = this;
        if (!document.getElementById('mothershipSupportButton')) {
            this.createButton('rapid-actions-wrapper', 'mothershipSupportButton', dojo.string.substitute(_("Use ${card_name}") + " : " + formatTextIcons("".concat(_('Gain ${hearts}[Heart]'), " (1[Energy])")), { card_name: this.evolutionCardsManager.getCardName(27, 'text-only'), hearts: 1 }), function () { return _this.useMothershipSupport(); }, this.gamedatas.players[this.getPlayerId()].mothershipSupportUsed || userEnergy < 1 || isMaxHealth);
        }
    };
    KingOfTokyo.prototype.removeMothershipSupportButton = function () {
        if (document.getElementById('mothershipSupportButton')) {
            dojo.destroy('mothershipSupportButton');
        }
    };
    KingOfTokyo.prototype.addRapidCultistButtons = function (isMaxHealth) {
        var _this = this;
        if (!document.getElementById('rapidCultistButtons')) {
            dojo.place("<div id=\"rapidCultistButtons\"><span>".concat(dojo.string.substitute(_('Use ${card_name}'), { card_name: _('Cultist') }), " :</span></div>"), 'rapid-actions-wrapper');
            this.createButton('rapidCultistButtons', 'rapidCultistHealthButton', formatTextIcons("".concat(dojo.string.substitute(_('Gain ${hearts}[Heart]'), { hearts: 1 }))), function () { return _this.useRapidCultist(4); }, isMaxHealth);
            this.createButton('rapidCultistButtons', 'rapidCultistEnergyButton', formatTextIcons("".concat(dojo.string.substitute(_('Gain ${energy}[Energy]'), { energy: 1 }))), function () { return _this.useRapidCultist(5); });
        }
    };
    KingOfTokyo.prototype.removeRapidCultistButtons = function () {
        if (document.getElementById('rapidCultistButtons')) {
            dojo.destroy('rapidCultistButtons');
        }
    };
    KingOfTokyo.prototype.checkRapidHealingButtonState = function () {
        if (document.getElementById('rapidHealingButton')) {
            var playerId = this.getPlayerId();
            var userEnergy = this.energyCounters[playerId].getValue();
            var health = this.healthCounters[playerId].getValue();
            var maxHealth = this.gamedatas.players[playerId].maxHealth;
            dojo.toggleClass('rapidHealingButton', 'disabled', userEnergy < 2 || health >= maxHealth);
        }
    };
    KingOfTokyo.prototype.checkMothershipSupportButtonState = function () {
        if (document.getElementById('mothershipSupportButton')) {
            var playerId = this.getPlayerId();
            var userEnergy = this.energyCounters[playerId].getValue();
            var health = this.healthCounters[playerId].getValue();
            var maxHealth = this.gamedatas.players[playerId].maxHealth;
            var used = this.gamedatas.players[playerId].mothershipSupportUsed;
            dojo.toggleClass('mothershipSupportButton', 'disabled', used || userEnergy < 1 || health >= maxHealth);
        }
    };
    KingOfTokyo.prototype.checkHealthCultistButtonState = function () {
        if (document.getElementById('rapidCultistHealthButton')) {
            var playerId = this.getPlayerId();
            var health = this.healthCounters[playerId].getValue();
            var maxHealth = this.gamedatas.players[playerId].maxHealth;
            dojo.toggleClass('rapidCultistHealthButton', 'disabled', health >= maxHealth);
        }
    };
    KingOfTokyo.prototype.addSkipBuyPhaseToggle = function (active) {
        var _this = this;
        if (!document.getElementById('skipBuyPhaseWrapper')) {
            dojo.place("<div id=\"skipBuyPhaseWrapper\">\n                <label class=\"switch\">\n                    <input id=\"skipBuyPhaseCheckbox\" type=\"checkbox\" ".concat(active ? 'checked' : '', ">\n                    <span class=\"slider round\"></span>\n                </label>\n                <label for=\"skipBuyPhaseCheckbox\" class=\"text-label\">").concat(_("Skip buy phase"), "</label>\n            </div>"), 'rapid-actions-wrapper');
            document.getElementById('skipBuyPhaseCheckbox').addEventListener('change', function (e) { return _this.setSkipBuyPhase(e.target.checked); });
        }
    };
    KingOfTokyo.prototype.removeSkipBuyPhaseToggle = function () {
        if (document.getElementById('skipBuyPhaseWrapper')) {
            dojo.destroy('skipBuyPhaseWrapper');
        }
    };
    KingOfTokyo.prototype.addAutoLeaveUnderButton = function () {
        var _this = this;
        if (!document.getElementById('autoLeaveUnderButton')) {
            this.createButton('rapid-actions-wrapper', 'autoLeaveUnderButton', _("Leave Tokyo") + ' &#x25BE;', function () { return _this.toggleAutoLeaveUnderPopin(); });
        }
    };
    KingOfTokyo.prototype.removeAutoLeaveUnderButton = function () {
        if (document.getElementById('autoLeaveUnderButton')) {
            dojo.destroy('autoLeaveUnderButton');
        }
    };
    KingOfTokyo.prototype.toggleAutoLeaveUnderPopin = function () {
        var bubble = document.getElementById("discussion_bubble_autoLeaveUnder");
        if ((bubble === null || bubble === void 0 ? void 0 : bubble.dataset.visible) === 'true') {
            this.closeAutoLeaveUnderPopin();
        }
        else {
            this.openAutoLeaveUnderPopin();
        }
    };
    KingOfTokyo.prototype.openAutoLeaveUnderPopin = function () {
        var _this = this;
        var popinId = "discussion_bubble_autoLeaveUnder";
        var bubble = document.getElementById(popinId);
        if (!bubble) {
            var maxHealth = this.gamedatas.players[this.getPlayerId()].maxHealth;
            var html = "<div id=\"".concat(popinId, "\" class=\"discussion_bubble autoLeaveUnderBubble\">\n                <div>").concat(_("Automatically leave tokyo when life goes down to, or under"), "</div>\n                <div id=\"").concat(popinId, "-buttons\" class=\"button-grid\">");
            for (var i = maxHealth; i > 0; i--) {
                html += "<button class=\"action-button bgabutton ".concat(this.gamedatas.leaveTokyoUnder === i || (i == 1 && !this.gamedatas.leaveTokyoUnder) ? 'bgabutton_blue' : 'bgabutton_gray', " autoLeaveButton ").concat(i == 1 ? 'disable' : '', "\" id=\"").concat(popinId, "_set").concat(i, "\">\n                    ").concat(i == 1 ? _('Disabled') : i - 1, "\n                </button>");
            }
            html += "</div>\n            <div>".concat(_("If your life is over it, or if disabled, you'll be asked if you want to stay or leave"), "</div>\n            <hr>\n            <div>").concat(_("Automatically stay in tokyo when life is at least"), "</div>\n                <div id=\"").concat(popinId, "-stay-buttons\" class=\"button-grid\">");
            for (var i = maxHealth + 1; i > 2; i--) {
                html += "<button class=\"action-button bgabutton ".concat(this.gamedatas.stayTokyoOver === i ? 'bgabutton_blue' : 'bgabutton_gray', " autoStayButton ").concat(this.gamedatas.leaveTokyoUnder > 0 && i <= this.gamedatas.leaveTokyoUnder ? 'disabled' : '', "\" id=\"").concat(popinId, "_setStay").concat(i, "\">").concat(i - 1, "</button>");
            }
            html += "<button class=\"action-button bgabutton ".concat(!this.gamedatas.stayTokyoOver ? 'bgabutton_blue' : 'bgabutton_gray', " autoStayButton disable\" id=\"").concat(popinId, "_setStay0\">").concat(_('Disabled'), "</button>");
            html += "</div>\n            </div>";
            dojo.place(html, 'autoLeaveUnderButton');
            var _loop_8 = function (i) {
                document.getElementById("".concat(popinId, "_set").concat(i)).addEventListener('click', function () {
                    _this.setLeaveTokyoUnder(i);
                    setTimeout(function () { return _this.closeAutoLeaveUnderPopin(); }, 100);
                });
            };
            for (var i = maxHealth; i > 0; i--) {
                _loop_8(i);
            }
            var _loop_9 = function (i) {
                document.getElementById("".concat(popinId, "_setStay").concat(i)).addEventListener('click', function () {
                    _this.setStayTokyoOver(i);
                    setTimeout(function () { return _this.closeAutoLeaveUnderPopin(); }, 100);
                });
            };
            for (var i = maxHealth + 1; i > 2; i--) {
                _loop_9(i);
            }
            document.getElementById("".concat(popinId, "_setStay0")).addEventListener('click', function () {
                _this.setStayTokyoOver(0);
                setTimeout(function () { return _this.closeAutoLeaveUnderPopin(); }, 100);
            });
            bubble = document.getElementById(popinId);
        }
        bubble.style.display = 'block';
        bubble.dataset.visible = 'true';
    };
    KingOfTokyo.prototype.updateAutoLeavePopinButtons = function () {
        var _this = this;
        var popinId = "discussion_bubble_autoLeaveUnder";
        var maxHealth = this.gamedatas.players[this.getPlayerId()].maxHealth;
        for (var i = maxHealth + 1; i <= 14; i++) {
            if (document.getElementById("".concat(popinId, "_set").concat(i))) {
                dojo.destroy("".concat(popinId, "_set").concat(i));
            }
            if (document.getElementById("".concat(popinId, "_setStay").concat(i))) {
                dojo.destroy("".concat(popinId, "_setStay").concat(i));
            }
        }
        var _loop_10 = function (i) {
            if (!document.getElementById("".concat(popinId, "_set").concat(i))) {
                dojo.place("<button class=\"action-button bgabutton ".concat(this_5.gamedatas.leaveTokyoUnder === i ? 'bgabutton_blue' : 'bgabutton_gray', " autoLeaveButton\" id=\"").concat(popinId, "_set").concat(i, "\">\n                    ").concat(i - 1, "\n                </button>"), "".concat(popinId, "-buttons"), 'first');
                document.getElementById("".concat(popinId, "_set").concat(i)).addEventListener('click', function () {
                    _this.setLeaveTokyoUnder(i);
                    setTimeout(function () { return _this.closeAutoLeaveUnderPopin(); }, 100);
                });
            }
        };
        var this_5 = this;
        for (var i = 11; i <= maxHealth; i++) {
            _loop_10(i);
        }
        var _loop_11 = function (i) {
            if (!document.getElementById("".concat(popinId, "_setStay").concat(i))) {
                dojo.place("<button class=\"action-button bgabutton ".concat(this_6.gamedatas.stayTokyoOver === i ? 'bgabutton_blue' : 'bgabutton_gray', " autoStayButton ").concat(this_6.gamedatas.leaveTokyoUnder > 0 && i <= this_6.gamedatas.leaveTokyoUnder ? 'disabled' : '', "\" id=\"").concat(popinId, "_setStay").concat(i, "\">\n                    ").concat(i - 1, "\n                </button>"), "".concat(popinId, "-stay-buttons"), 'first');
                document.getElementById("".concat(popinId, "_setStay").concat(i)).addEventListener('click', function () {
                    _this.setStayTokyoOver(i);
                    setTimeout(function () { return _this.closeAutoLeaveUnderPopin(); }, 100);
                });
            }
        };
        var this_6 = this;
        for (var i = 12; i <= maxHealth + 1; i++) {
            _loop_11(i);
        }
    };
    KingOfTokyo.prototype.closeAutoLeaveUnderPopin = function () {
        var bubble = document.getElementById("discussion_bubble_autoLeaveUnder");
        if (bubble) {
            bubble.style.display = 'none';
            bubble.dataset.visible = 'false';
        }
    };
    KingOfTokyo.prototype.addAutoSkipPlayEvolutionButton = function () {
        var _this = this;
        if (!document.getElementById('autoSkipPlayEvolutionButton')) {
            this.createButton('autoSkipPlayEvolution-wrapper', 'autoSkipPlayEvolutionButton', _("Ask to play evolution") + ' &#x25BE;', function () { return _this.toggleAutoSkipPlayEvolutionPopin(); });
        }
    };
    KingOfTokyo.prototype.toggleAutoSkipPlayEvolutionPopin = function () {
        var bubble = document.getElementById("discussion_bubble_autoSkipPlayEvolution");
        if ((bubble === null || bubble === void 0 ? void 0 : bubble.dataset.visible) === 'true') {
            this.closeAutoSkipPlayEvolutionPopin();
        }
        else {
            this.openAutoSkipPlayEvolutionPopin();
        }
    };
    KingOfTokyo.prototype.openAutoSkipPlayEvolutionPopin = function () {
        var _this = this;
        var popinId = "discussion_bubble_autoSkipPlayEvolution";
        var bubble = document.getElementById(popinId);
        if (!bubble) {
            var html = "<div id=\"".concat(popinId, "\" class=\"discussion_bubble autoSkipPlayEvolutionBubble\">\n                <h3>").concat(_("Ask to play Evolution, for Evolutions playable on specific occasions"), "</h3>\n                <div class=\"autoSkipPlayEvolution-option\">\n                    <input type=\"radio\" name=\"autoSkipPlayEvolution\" value=\"0\" id=\"autoSkipPlayEvolution-all\" />\n                    <label for=\"autoSkipPlayEvolution-all\">\n                        ").concat(_("Ask for every specific occasion even if I don't have the card in my hand."), "\n                        <div class=\"label-detail\">\n                            ").concat(_("Recommended. You won't be asked when your hand is empty"), "\n                        </div>\n                    </label>\n                </div>\n                <div class=\"autoSkipPlayEvolution-option\">\n                    <input type=\"radio\" name=\"autoSkipPlayEvolution\" value=\"1\" id=\"autoSkipPlayEvolution-real\" />\n                    <label for=\"autoSkipPlayEvolution-real\">\n                        ").concat(_("Ask only if I have in my hand an Evolution matching the specific occasion."), "<br>\n                        <div class=\"label-detail spe-warning\">\n                            <strong>").concat(_("Warning:"), "</strong> ").concat(_("Your opponent can deduce what you have in hand with this option."), "\n                        </div>\n                    </label>\n                </div>\n                <div class=\"autoSkipPlayEvolution-option\">\n                    <input type=\"radio\" name=\"autoSkipPlayEvolution\" value=\"2\" id=\"autoSkipPlayEvolution-turn\" />\n                    <label for=\"autoSkipPlayEvolution-turn\">\n                        ").concat(_("Do not ask until my next turn."), "<br>\n                        <div class=\"label-detail spe-warning\">\n                            <strong>").concat(_("Warning:"), "</strong> ").concat(_("Do it only if you're sure you won't need an Evolution soon."), "\n                        </div>\n                    </label>\n                </div>\n                <div class=\"autoSkipPlayEvolution-option\">\n                    <input type=\"radio\" name=\"autoSkipPlayEvolution\" value=\"3\" id=\"autoSkipPlayEvolution-off\" />\n                    <label for=\"autoSkipPlayEvolution-off\">\n                        ").concat(_("Do not ask until I turn it back on."), "\n                        <div class=\"label-detail spe-warning\">\n                            <strong>").concat(_("Warning:"), "</strong> ").concat(_("Do it only if you're sure you won't need an Evolution soon."), "\n                        </div>\n                    </label>\n                </div>\n            </div>");
            dojo.place(html, 'autoSkipPlayEvolutionButton');
            Array.from(document.querySelectorAll('input[name="autoSkipPlayEvolution"]')).forEach(function (input) {
                input.addEventListener('change', function () {
                    var value = document.querySelector('input[name="autoSkipPlayEvolution"]:checked').value;
                    _this.setAskPlayEvolution(Number(value));
                    setTimeout(function () { return _this.closeAutoSkipPlayEvolutionPopin(); }, 100);
                });
            });
            bubble = document.getElementById(popinId);
            this.notif_updateAskPlayEvolution({
                args: {
                    value: this.gamedatas.askPlayEvolution
                }
            });
        }
        bubble.style.display = 'block';
        bubble.dataset.visible = 'true';
    };
    KingOfTokyo.prototype.closeAutoSkipPlayEvolutionPopin = function () {
        var bubble = document.getElementById("discussion_bubble_autoSkipPlayEvolution");
        if (bubble) {
            bubble.style.display = 'none';
            bubble.dataset.visible = 'false';
        }
    };
    KingOfTokyo.prototype.setMimicToken = function (type, card) {
        var _this = this;
        if (!card) {
            return;
        }
        this.playerTables.forEach(function (playerTable) {
            if (playerTable.cards.getCards().some(function (item) { return Number(item.id) == card.id; })) {
                _this.cardsManager.placeMimicOnCard(type, card, _this.wickednessTilesManager);
            }
        });
        this.setMimicTooltip(type, card);
    };
    KingOfTokyo.prototype.removeMimicToken = function (type, card) {
        var _this = this;
        this.setMimicTooltip(type, null);
        if (!card) {
            return;
        }
        this.playerTables.forEach(function (playerTable) {
            if (playerTable.cards.getCards().some(function (item) { return Number(item.id) == card.id; })) {
                _this.cardsManager.removeMimicOnCard(type, card);
            }
        });
    };
    KingOfTokyo.prototype.setMimicEvolutionToken = function (card) {
        if (!card) {
            return;
        }
        this.evolutionCardsManager.placeMimicOnCard(card);
        this.setMimicEvolutionTooltip(card);
    };
    KingOfTokyo.prototype.setMimicTooltip = function (type, mimickedCard) {
        var _this = this;
        this.playerTables.forEach(function (playerTable) {
            var mimicCardId = type === 'tile' ? 106 : 27;
            var cards = (type === 'tile' ? playerTable.wickednessTiles : playerTable.cards).getCards();
            var mimicCardItem = cards.find(function (item) { return Number(item.type) == mimicCardId; });
            if (mimicCardItem) {
                var cardManager = type === 'tile' ? _this.wickednessTilesManager : _this.cardsManager;
                cardManager.changeMimicTooltip(cardManager.getId(mimicCardItem), _this.cardsManager.getMimickedCardText(mimickedCard));
            }
        });
    };
    KingOfTokyo.prototype.setMimicEvolutionTooltip = function (mimickedCard) {
        var _this = this;
        this.playerTables.forEach(function (playerTable) {
            var mimicCardItem = playerTable.visibleEvolutionCards.getCards().find(function (item) { return Number(item.type) == 18; });
            if (mimicCardItem) {
                _this.evolutionCardsManager.changeMimicTooltip(_this.evolutionCardsManager.getId(mimicCardItem), _this.evolutionCardsManager.getMimickedCardText(mimickedCard));
            }
        });
    };
    KingOfTokyo.prototype.removeMimicEvolutionToken = function (card) {
        var _this = this;
        this.setMimicEvolutionTooltip(null);
        if (!card) {
            return;
        }
        this.playerTables.forEach(function (playerTable) {
            if (playerTable.cards.getCards().some(function (item) { return Number(item.id) == card.id; })) {
                _this.evolutionCardsManager.removeMimicOnCard(card);
            }
        });
    };
    KingOfTokyo.prototype.showEvolutionsPopin = function (cardsTypes, title) {
        var viewCardsDialog = new ebg.popindialog();
        viewCardsDialog.create('kotViewEvolutionsDialog');
        viewCardsDialog.setTitle(title);
        var html = "<div id=\"see-monster-evolutions\"></div>";
        // Show the dialog
        viewCardsDialog.setContent(html);
        var stock = new LineStock(this.evolutionCardsManager, document.getElementById('see-monster-evolutions'));
        stock.addCards(cardsTypes.map(function (cardType, index) { return ({ id: 100000 + index, type: cardType }); }));
        viewCardsDialog.show();
        // Replace the function call when it's clicked
        viewCardsDialog.replaceCloseCallback(function () {
            stock.remove();
            viewCardsDialog.destroy();
        });
    };
    KingOfTokyo.prototype.showPlayerEvolutions = function (playerId) {
        var cardsTypes = this.gamedatas.players[playerId].ownedEvolutions.map(function (evolution) { return evolution.type; });
        this.showEvolutionsPopin(cardsTypes, dojo.string.substitute(_("Evolution cards owned by ${player_name}"), { 'player_name': this.gamedatas.players[playerId].name }));
    };
    KingOfTokyo.prototype.showDiscardCards = function (cards, args) {
        var _this = this;
        var buyCardFromDiscardDialog = new ebg.popindialog();
        buyCardFromDiscardDialog.create('kotDiscardCardsDialog');
        buyCardFromDiscardDialog.setTitle(/*_TODOORI*/ ('Discard cards'));
        var html = "<div id=\"see-monster-evolutions\"></div>";
        // Show the dialog
        buyCardFromDiscardDialog.setContent(html);
        buyCardFromDiscardDialog.show();
        var stock = new LineStock(this.cardsManager, document.getElementById('see-monster-evolutions'));
        stock.addCards(cards);
        stock.onCardClick = function (card) {
            _this.onVisibleCardClick(stock, card);
            stock.removeAll();
            buyCardFromDiscardDialog.destroy();
        };
        stock.setSelectionMode('single');
        this.setBuyDisabledCardByCostForStock(args.disabledIds, this.getCardCosts(args), this.energyCounters[this.getPlayerId()].getValue(), this.tableCenter.getVisibleCards());
        buyCardFromDiscardDialog.show();
        // Replace the function call when it's clicked
        buyCardFromDiscardDialog.replaceCloseCallback(function () {
            stock.removeAll();
            buyCardFromDiscardDialog.destroy();
        });
    };
    KingOfTokyo.prototype.getNoExtraTurnWarningMessage = function () {
        return _('As you are in a Mindbug turn, you cannot befenit from the extra turn effect');
    };
    KingOfTokyo.prototype.pickMonster = function (monster) {
        this.bgaPerformAction('actPickMonster', {
            monster: monster
        });
    };
    KingOfTokyo.prototype.pickEvolutionForDeck = function (id) {
        this.bgaPerformAction('actPickEvolutionForDeck', {
            id: id
        });
    };
    KingOfTokyo.prototype.chooseInitialCard = function (id, evolutionId) {
        this.bgaPerformAction('actChooseInitialCard', {
            id: id,
            evolutionId: evolutionId,
        });
    };
    KingOfTokyo.prototype.skipBeforeStartTurn = function () {
        this.bgaPerformAction('actSkipBeforeStartTurn');
    };
    KingOfTokyo.prototype.skipBeforeEndTurn = function () {
        this.bgaPerformAction('actSkipBeforeEndTurn');
    };
    KingOfTokyo.prototype.skipBeforeEnteringTokyo = function () {
        this.bgaPerformAction('actSkipBeforeEnteringTokyo');
    };
    KingOfTokyo.prototype.skipAfterEnteringTokyo = function () {
        this.bgaPerformAction('actSkipAfterEnteringTokyo');
    };
    KingOfTokyo.prototype.giveSymbolToActivePlayer = function (symbol) {
        this.bgaPerformAction('actGiveSymbolToActivePlayer', {
            symbol: symbol
        });
    };
    KingOfTokyo.prototype.giveSymbol = function (symbol) {
        this.bgaPerformAction('actGiveSymbol', {
            symbol: symbol
        });
    };
    KingOfTokyo.prototype.onRethrow = function () {
        this.rethrowDice(this.diceManager.destroyFreeDice());
    };
    KingOfTokyo.prototype.rethrowDice = function (diceIds) {
        this.bgaPerformAction('actRethrow', {
            diceIds: diceIds.join(',')
        });
    };
    KingOfTokyo.prototype.rethrow3 = function () {
        var lockedDice = this.diceManager.getLockedDice();
        this.bgaPerformAction('actRethrow3', {
            diceIds: lockedDice.map(function (die) { return die.id; }).join(',')
        });
    };
    KingOfTokyo.prototype.rerollDie = function (id) {
        var lockedDice = this.diceManager.getLockedDice();
        this.bgaPerformAction('actRerollDie', {
            id: id,
            diceIds: lockedDice.map(function (die) { return die.id; }).join(',')
        });
    };
    KingOfTokyo.prototype.rethrow3camouflage = function () {
        this.bgaPerformAction('actRethrow3Camouflage');
    };
    KingOfTokyo.prototype.rethrow3psychicProbe = function () {
        this.bgaPerformAction('actRethrow3PsychicProbe');
    };
    KingOfTokyo.prototype.rethrow3changeDie = function () {
        this.bgaPerformAction('actRethrow3ChangeDie');
    };
    KingOfTokyo.prototype.buyEnergyDrink = function () {
        var diceIds = this.diceManager.destroyFreeDice();
        this.bgaPerformAction('actBuyEnergyDrink', {
            diceIds: diceIds.join(',')
        });
    };
    KingOfTokyo.prototype.useSmokeCloud = function () {
        var diceIds = this.diceManager.destroyFreeDice();
        this.bgaPerformAction('actUseSmokeCloud', {
            diceIds: diceIds.join(',')
        });
    };
    KingOfTokyo.prototype.useCultist = function () {
        var diceIds = this.diceManager.destroyFreeDice();
        this.bgaPerformAction('actUseCultist', {
            diceIds: diceIds.join(',')
        });
    };
    KingOfTokyo.prototype.useRapidHealing = function () {
        this.bgaPerformAction('actUseRapidHealing', null, { lock: false, checkAction: false });
    };
    KingOfTokyo.prototype.useMothershipSupport = function () {
        this.bgaPerformAction('actUseMothershipSupport', null, { lock: false, checkAction: false });
    };
    KingOfTokyo.prototype.useRapidCultist = function (type) {
        this.bgaPerformAction('actUseRapidCultist', { type: type }, { lock: false, checkAction: false });
    };
    KingOfTokyo.prototype.setSkipBuyPhase = function (skipBuyPhase) {
        this.bgaPerformAction('actSetSkipBuyPhase', {
            skipBuyPhase: skipBuyPhase
        }, { lock: false, checkAction: false });
    };
    KingOfTokyo.prototype.changeDie = function (id, value, card, cardId) {
        this.bgaPerformAction('actChangeDie', {
            id: id,
            value: value,
            card: card,
            cardId: cardId,
        });
    };
    KingOfTokyo.prototype.psychicProbeRollDie = function (id) {
        this.bgaPerformAction('actChangeActivePlayerDie', {
            id: id
        });
    };
    KingOfTokyo.prototype.goToChangeDie = function (confirmed) {
        var _this = this;
        if (confirmed === void 0) { confirmed = false; }
        var args = this.gamedatas.gamestate.args;
        if (!confirmed && args.throwNumber == 1 && args.maxThrowNumber > 1) {
            this.confirmationDialog(formatTextIcons(_('Are you sure you want to resolve dice without any reroll? If you want to change your dice, click on the dice you want to keep and use "Reroll dice" button to reroll the others.')), function () { return _this.goToChangeDie(true); });
            return;
        }
        this.bgaPerformAction('actGoToChangeDie');
    };
    KingOfTokyo.prototype.resolveDice = function () {
        this.bgaPerformAction('actResolve');
    };
    KingOfTokyo.prototype.support = function () {
        this.bgaPerformAction('actSupport');
    };
    KingOfTokyo.prototype.dontSupport = function () {
        this.bgaPerformAction('actDontSupport');
    };
    KingOfTokyo.prototype.discardDie = function (id) {
        this.bgaPerformAction('actDiscardDie', {
            id: id
        });
    };
    KingOfTokyo.prototype.rerollOrDiscardDie = function (id) {
        if (!this.falseBlessingAnkhAction) {
            return;
        }
        this.bgaPerformAction(this.falseBlessingAnkhAction, {
            id: id
        });
    };
    KingOfTokyo.prototype.freezeDie = function (id) {
        this.bgaPerformAction('actFreezeDie', {
            id: id
        });
    };
    KingOfTokyo.prototype.skipFreezeDie = function () {
        this.bgaPerformAction('actSkipFreezeDie');
    };
    KingOfTokyo.prototype.discardKeepCard = function (id) {
        this.bgaPerformAction('actDiscardKeepCard', {
            id: id
        });
    };
    KingOfTokyo.prototype.giveGoldenScarab = function (playerId) {
        this.bgaPerformAction('actGiveGoldenScarab', {
            playerId: playerId
        });
    };
    KingOfTokyo.prototype.giveSymbols = function (symbols) {
        this.bgaPerformAction('actGiveSymbols', {
            symbols: symbols.join(',')
        });
    };
    KingOfTokyo.prototype.selectExtraDie = function (face) {
        this.bgaPerformAction('actSelectExtraDie', {
            face: face
        });
    };
    KingOfTokyo.prototype.falseBlessingReroll = function (id) {
        this.bgaPerformAction('actFalseBlessingReroll', {
            id: id
        });
    };
    KingOfTokyo.prototype.falseBlessingDiscard = function (id) {
        this.bgaPerformAction('actFalseBlessingDiscard', {
            id: id
        });
    };
    KingOfTokyo.prototype.falseBlessingSkip = function () {
        this.bgaPerformAction('actFalseBlessingSkip');
    };
    KingOfTokyo.prototype.rerollDice = function (diceIds) {
        this.bgaPerformAction('actRerollDice', {
            ids: diceIds.join(',')
        });
    };
    KingOfTokyo.prototype.takeWickednessTile = function (id) {
        this.bgaPerformAction('actTakeWickednessTile', {
            id: id,
        });
    };
    KingOfTokyo.prototype.skipTakeWickednessTile = function () {
        this.bgaPerformAction('actSkipTakeWickednessTile');
    };
    KingOfTokyo.prototype.applyHeartActions = function (selections) {
        this.bgaPerformAction('actApplyHeartDieChoices', {
            heartDieChoices: JSON.stringify(selections)
        });
    };
    KingOfTokyo.prototype.applySmashActions = function (selections) {
        console.warn(selections);
        this.bgaPerformAction('actApplySmashDieChoices', {
            smashDieChoices: JSON.stringify(selections)
        });
    };
    KingOfTokyo.prototype.chooseEvolutionCard = function (id) {
        this.bgaPerformAction('actChooseEvolutionCard', {
            id: id
        });
    };
    KingOfTokyo.prototype.onStayInTokyo = function () {
        this.bgaPerformAction('actStay');
    };
    KingOfTokyo.prototype.onLeaveTokyo = function (useCard) {
        this.bgaPerformAction('actLeave', { useCard: useCard });
    };
    KingOfTokyo.prototype.stealCostumeCard = function (id) {
        this.bgaPerformAction('actStealCostumeCard', {
            id: id
        });
    };
    KingOfTokyo.prototype.changeForm = function () {
        this.bgaPerformAction('actChangeForm');
    };
    KingOfTokyo.prototype.skipChangeForm = function () {
        this.bgaPerformAction('actSkipChangeForm');
    };
    KingOfTokyo.prototype.buyCard = function (id, from, useSuperiorAlienTechnology, useBobbingForApples) {
        if (useSuperiorAlienTechnology === void 0) { useSuperiorAlienTechnology = false; }
        if (useBobbingForApples === void 0) { useBobbingForApples = false; }
        this.bgaPerformAction('actBuyCard', {
            id: id,
            from: from,
            useSuperiorAlienTechnology: useSuperiorAlienTechnology,
            useBobbingForApples: useBobbingForApples
        });
    };
    KingOfTokyo.prototype.buyCardBamboozle = function (id, from) {
        this.bgaPerformAction('actBuyCardBamboozle', {
            id: id,
            from: from
        });
    };
    KingOfTokyo.prototype.chooseMimickedCard = function (id) {
        this.bgaPerformAction('actChooseMimickedCard', {
            id: id
        });
    };
    KingOfTokyo.prototype.chooseMimickedEvolution = function (id) {
        this.bgaPerformAction('actChooseMimickedEvolution', {
            id: id
        });
    };
    KingOfTokyo.prototype.changeMimickedCard = function (id) {
        this.bgaPerformAction('actChangeMimickedCard', {
            id: id
        });
    };
    KingOfTokyo.prototype.chooseMimickedCardWickednessTile = function (id) {
        this.bgaPerformAction('actChooseMimickedCardWickednessTile', {
            id: id
        });
    };
    KingOfTokyo.prototype.changeMimickedCardWickednessTile = function (id) {
        this.bgaPerformAction('actChangeMimickedCardWickednessTile', {
            id: id
        });
    };
    KingOfTokyo.prototype.sellCard = function (id) {
        this.bgaPerformAction('actSellCard', {
            id: id
        });
    };
    KingOfTokyo.prototype.onRenew = function (cardType) {
        this.bgaPerformAction('actRenew', {
            cardType: cardType
        });
    };
    KingOfTokyo.prototype.skipCardIsBought = function () {
        this.bgaPerformAction('actSkipCardIsBought');
    };
    KingOfTokyo.prototype.goToSellCard = function () {
        this.bgaPerformAction('actGoToSellCard');
    };
    KingOfTokyo.prototype.opportunistSkip = function () {
        this.bgaPerformAction('actOpportunistSkip');
    };
    KingOfTokyo.prototype.changeActivePlayerDieSkip = function () {
        this.bgaPerformAction('actChangeActivePlayerDieSkip');
    };
    KingOfTokyo.prototype.skipChangeMimickedCard = function () {
        this.bgaPerformAction('actSkipChangeMimickedCard');
    };
    KingOfTokyo.prototype.skipChangeMimickedCardWickednessTile = function () {
        this.bgaPerformAction('actSkipChangeMimickedCardWickednessTile');
    };
    KingOfTokyo.prototype.endStealCostume = function () {
        this.bgaPerformAction('actEndStealCostume');
    };
    KingOfTokyo.prototype.onEndTurn = function () {
        this.bgaPerformAction('actEndTurn');
    };
    KingOfTokyo.prototype.throwCamouflageDice = function () {
        this.bgaPerformAction('actThrowCamouflageDice');
    };
    KingOfTokyo.prototype.useWings = function () {
        this.bgaPerformAction('actUseWings');
    };
    KingOfTokyo.prototype.useInvincibleEvolution = function (evolutionType) {
        this.bgaPerformAction('actUseInvincibleEvolution', {
            evolutionType: evolutionType
        });
    };
    KingOfTokyo.prototype.useCandyEvolution = function () {
        this.bgaPerformAction('actUseCandyEvolution');
    };
    KingOfTokyo.prototype.skipWings = function () {
        this.bgaPerformAction('actSkipWings');
    };
    KingOfTokyo.prototype.useRobot = function (energy) {
        this.bgaPerformAction('actUseRobot', {
            energy: energy
        });
    };
    KingOfTokyo.prototype.useElectricArmor = function (energy) {
        this.bgaPerformAction('actUseElectricArmor', {
            energy: energy
        });
    };
    KingOfTokyo.prototype.useSuperJump = function (energy) {
        this.bgaPerformAction('actUseSuperJump', {
            energy: energy
        });
    };
    KingOfTokyo.prototype.useRapidHealingSync = function (cultistCount, rapidHealingCount) {
        this.bgaPerformAction('actUseRapidHealingSync', {
            cultistCount: cultistCount,
            rapidHealingCount: rapidHealingCount
        });
    };
    KingOfTokyo.prototype.setLeaveTokyoUnder = function (under) {
        this.bgaPerformAction('setLeaveTokyoUnder', {
            under: under
        }, { lock: false, checkAction: false });
    };
    KingOfTokyo.prototype.setStayTokyoOver = function (over) {
        this.bgaPerformAction('setStayTokyoOver', {
            over: over
        }, { lock: false, checkAction: false });
    };
    KingOfTokyo.prototype.setAskPlayEvolution = function (value) {
        this.bgaPerformAction('setAskPlayEvolution', {
            value: value
        }, { lock: false, checkAction: false });
    };
    KingOfTokyo.prototype.exchangeCard = function (id) {
        this.bgaPerformAction('actExchangeCard', {
            id: id
        });
    };
    KingOfTokyo.prototype.skipExchangeCard = function () {
        this.bgaPerformAction('actSkipExchangeCard');
    };
    KingOfTokyo.prototype.stayInHibernation = function () {
        this.bgaPerformAction('actStayInHibernation');
    };
    KingOfTokyo.prototype.leaveHibernation = function () {
        this.bgaPerformAction('actLeaveHibernation');
    };
    KingOfTokyo.prototype.playEvolution = function (id) {
        this.bgaPerformAction('actPlayEvolution', {
            id: id
        }, { checkAction: false, lock: false });
    };
    KingOfTokyo.prototype.giveGiftEvolution = function (id, toPlayerId) {
        this.bgaPerformAction('actGiveGiftEvolution', {
            id: id,
            toPlayerId: toPlayerId,
        });
    };
    KingOfTokyo.prototype.useYinYang = function () {
        this.bgaPerformAction('actUseYinYang');
    };
    KingOfTokyo.prototype.putEnergyOnBambooSupply = function () {
        this.bgaPerformAction('actPutEnergyOnBambooSupply');
    };
    KingOfTokyo.prototype.takeEnergyOnBambooSupply = function () {
        this.bgaPerformAction('actTakeEnergyOnBambooSupply');
    };
    KingOfTokyo.prototype.gazeOfTheSphinxDrawEvolution = function () {
        this.bgaPerformAction('actGazeOfTheSphinxDrawEvolution');
    };
    KingOfTokyo.prototype.gazeOfTheSphinxGainEnergy = function () {
        this.bgaPerformAction('actGazeOfTheSphinxGainEnergy');
    };
    KingOfTokyo.prototype.gazeOfTheSphinxDiscardEvolution = function (id) {
        this.bgaPerformAction('actGazeOfTheSphinxDiscardEvolution', {
            id: id
        });
    };
    KingOfTokyo.prototype.gazeOfTheSphinxLoseEnergy = function () {
        this.bgaPerformAction('actGazeOfTheSphinxLoseEnergy');
    };
    KingOfTokyo.prototype.useChestThumping = function (id) {
        this.bgaPerformAction('actUseChestThumping', {
            id: id
        });
    };
    KingOfTokyo.prototype.skipChestThumping = function () {
        this.bgaPerformAction('actSkipChestThumping');
    };
    KingOfTokyo.prototype.chooseFreezeRayDieFace = function (symbol) {
        this.bgaPerformAction('actChooseFreezeRayDieFace', {
            symbol: symbol
        });
    };
    KingOfTokyo.prototype.useMiraculousCatch = function () {
        this.bgaPerformAction('actUseMiraculousCatch');
    };
    KingOfTokyo.prototype.buyCardMiraculousCatch = function (useSuperiorAlienTechnology) {
        if (useSuperiorAlienTechnology === void 0) { useSuperiorAlienTechnology = false; }
        this.bgaPerformAction('actBuyCardMiraculousCatch', {
            useSuperiorAlienTechnology: useSuperiorAlienTechnology,
        });
    };
    KingOfTokyo.prototype.skipMiraculousCatch = function () {
        this.bgaPerformAction('actSkipMiraculousCatch');
    };
    KingOfTokyo.prototype.playCardDeepDive = function (id) {
        this.bgaPerformAction('actPlayCardDeepDive', {
            id: id
        });
    };
    KingOfTokyo.prototype.useExoticArms = function () {
        this.bgaPerformAction('actUseExoticArms');
    };
    KingOfTokyo.prototype.skipExoticArms = function () {
        this.bgaPerformAction('actSkipExoticArms');
    };
    KingOfTokyo.prototype.skipBeforeResolveDice = function () {
        this.bgaPerformAction('actSkipBeforeResolveDice');
    };
    KingOfTokyo.prototype.giveTarget = function () {
        this.bgaPerformAction('actGiveTarget');
    };
    KingOfTokyo.prototype.skipGiveTarget = function () {
        this.bgaPerformAction('actSkipGiveTarget');
    };
    KingOfTokyo.prototype.useLightningArmor = function () {
        this.bgaPerformAction('actUseLightningArmor');
    };
    KingOfTokyo.prototype.skipLightningArmor = function () {
        this.bgaPerformAction('actSkipLightningArmor');
    };
    KingOfTokyo.prototype.answerEnergySword = function (use) {
        this.bgaPerformAction('actAnswerEnergySword', { use: use });
    };
    KingOfTokyo.prototype.answerSunkenTemple = function (use) {
        this.bgaPerformAction('actAnswerSunkenTemple', { use: use });
    };
    KingOfTokyo.prototype.answerElectricCarrot = function (choice) {
        this.bgaPerformAction('actAnswerElectricCarrot', { choice: choice });
    };
    KingOfTokyo.prototype.reserveCard = function (id) {
        this.bgaPerformAction('actReserveCard', { id: id });
    };
    KingOfTokyo.prototype.useFelineMotor = function () {
        this.bgaPerformAction('actUseFelineMotor');
    };
    KingOfTokyo.prototype.throwDieSuperiorAlienTechnology = function () {
        this.bgaPerformAction('actThrowDieSuperiorAlienTechnology');
    };
    KingOfTokyo.prototype.freezeRayChooseOpponent = function (playerId) {
        this.bgaPerformAction('actFreezeRayChooseOpponent', { playerId: playerId });
    };
    KingOfTokyo.prototype.loseHearts = function () {
        this.bgaPerformAction('actLoseHearts');
    };
    KingOfTokyo.prototype.setFont = function (prefValue) {
        this.playerTables.forEach(function (playerTable) { return playerTable.setFont(prefValue); });
    };
    KingOfTokyo.prototype.startActionTimer = function (buttonId, time) {
        if (this.getGameUserPreference(202) === 2) {
            return;
        }
        var button = document.getElementById(buttonId);
        var actionTimerId = null;
        var _actionTimerLabel = button.innerHTML;
        var _actionTimerSeconds = time;
        var actionTimerFunction = function () {
            var button = document.getElementById(buttonId);
            if (button == null) {
                window.clearInterval(actionTimerId);
            }
            else if (_actionTimerSeconds-- > 1) {
                button.innerHTML = _actionTimerLabel + ' (' + _actionTimerSeconds + ')';
            }
            else {
                window.clearInterval(actionTimerId);
                button.click();
            }
        };
        actionTimerFunction();
        actionTimerId = window.setInterval(function () { return actionTimerFunction(); }, 1000);
    };
    ///////////////////////////////////////////////////
    //// Reaction to cometD notifications
    /*
        setupNotifications:

        In this method, you associate each of your game notifications with your local method to handle it.

        Note: game notification names correspond to "notifyAllPlayers" and "notifyPlayer" calls in
                your pylos.game.php file.

    */
    KingOfTokyo.prototype.setupNotifications = function () {
        //log( 'notifications subscriptions setup' );
        var _this = this;
        var notifs = [
            ['pickMonster', 500],
            ['setInitialCards', undefined],
            ['resolveNumberDice', ANIMATION_MS],
            ['resolveHealthDice', ANIMATION_MS],
            ['resolveHealingRay', ANIMATION_MS],
            ['resolveHealthDiceInTokyo', ANIMATION_MS],
            ['removeShrinkRayToken', ANIMATION_MS],
            ['removePoisonToken', ANIMATION_MS],
            ['resolveEnergyDice', ANIMATION_MS],
            ['resolveSmashDice', ANIMATION_MS],
            ['playerEliminated', ANIMATION_MS],
            ['playerEntersTokyo', ANIMATION_MS],
            ['renewCards', undefined],
            ['buyCard', ANIMATION_MS],
            ['reserveCard', ANIMATION_MS],
            ['leaveTokyo', ANIMATION_MS],
            ['useCamouflage', ANIMATION_MS],
            ['useLightningArmor', ANIMATION_MS],
            ['changeDie', ANIMATION_MS],
            ['changeDice', ANIMATION_MS],
            ['rethrow3changeDie', ANIMATION_MS],
            ['changeCurseCard', undefined],
            ['takeWickednessTile', ANIMATION_MS],
            ['changeGoldenScarabOwner', ANIMATION_MS],
            ['discardedDie', ANIMATION_MS],
            ['exchangeCard', ANIMATION_MS],
            ['playEvolution', ANIMATION_MS],
            ['superiorAlienTechnologyRolledDie', ANIMATION_MS],
            ['superiorAlienTechnologyLog', ANIMATION_MS],
            ['resolvePlayerDice', 500],
            ['changeTokyoTowerOwner', 500],
            ['changeForm', 500],
            ['evolutionPickedForDeck', 500],
            ['points', 1],
            ['health', 1],
            ['energy', 1],
            ['maxHealth', 1],
            ['wickedness', 1],
            ['shrinkRayToken', 1],
            ['poisonToken', 1],
            ['setCardTokens', 1],
            ['setEvolutionTokens', 1],
            ['setTileTokens', 1],
            ['removeCards', 1],
            ['removeEvolutions', 1],
            ['setMimicToken', 1],
            ['setMimicEvolutionToken', 1],
            ['removeMimicToken', 1],
            ['removeMimicEvolutionToken', 1],
            ['toggleRapidHealing', 1],
            ['toggleMothershipSupport', 1],
            ['toggleMothershipSupportUsed', 1],
            ['updateLeaveTokyoUnder', 1],
            ['updateStayTokyoOver', 1],
            ['updateAskPlayEvolution', 1],
            ['kotPlayerEliminated', 1],
            ['setPlayerBerserk', 1],
            ['cultist', 1],
            ['removeWickednessTiles', 1],
            ['addEvolutionCardInHand', 1],
            ['addSuperiorAlienTechnologyToken', 1],
            ['giveTarget', 1],
            ['updateCancelDamage', 1],
            ['ownedEvolutions', 1],
            ['resurrect', 1],
            ['mindbugPlayer', 1],
            ['setPlayerCounter', 1],
            ['log500', 500],
        ];
        notifs.forEach(function (notif) {
            dojo.subscribe(notif[0], _this, function (notifDetails) {
                log("notif_".concat(notif[0]), notifDetails.args);
                var promise = _this["notif_".concat(notif[0])](notifDetails.args);
                // tell the UI notification ends, if the function returned a promise
                promise === null || promise === void 0 ? void 0 : promise.then(function () { return _this.notifqueue.onSynchronousNotificationEnd(); });
            });
            _this.notifqueue.setSynchronous(notif[0], notif[1]);
        });
        if (isDebug) {
            notifs.forEach(function (notif) {
                if (!_this["notif_".concat(notif[0])]) {
                    console.warn("notif_".concat(notif[0], " function is not declared, but listed in setupNotifications"));
                }
            });
            Object.getOwnPropertyNames(KingOfTokyo.prototype).filter(function (item) { return item.startsWith('notif_'); }).map(function (item) { return item.slice(6); }).forEach(function (item) {
                if (!notifs.some(function (notif) { return notif[0] == item; })) {
                    console.warn("notif_".concat(item, " function is declared, but not listed in setupNotifications"));
                }
            });
        }
    };
    KingOfTokyo.prototype.notif_log500 = function () {
        // nothing, it's just for the delay
    };
    KingOfTokyo.prototype.notif_pickMonster = function (args) {
        var _this = this;
        var monsterDiv = document.getElementById("pick-monster-figure-".concat(args.monster));
        var destinationId = "player-board-monster-figure-".concat(args.playerId);
        var animation = this.slideToObject(monsterDiv, destinationId);
        dojo.connect(animation, 'onEnd', dojo.hitch(this, function () {
            _this.fadeOutAndDestroy(monsterDiv);
            dojo.removeClass(destinationId, 'monster0');
            dojo.addClass(destinationId, "monster".concat(args.monster));
        }));
        animation.play();
        this.getPlayerTable(args.playerId).setMonster(args.monster);
    };
    KingOfTokyo.prototype.notif_evolutionPickedForDeck = function (args) {
        this.inDeckEvolutionsStock.addCard(args.card, { fromStock: this.choseEvolutionInStock });
    };
    KingOfTokyo.prototype.notif_setInitialCards = function (args) {
        return this.tableCenter.setVisibleCards(args.cards, false, args.deckCardsCount, args.topDeckCard);
    };
    KingOfTokyo.prototype.notif_resolveNumberDice = function (args) {
        this.setPoints(args.playerId, args.points, ANIMATION_MS);
        this.kotAnimationManager.resolveNumberDice(args);
        this.diceManager.resolveNumberDice(args);
    };
    KingOfTokyo.prototype.notif_resolveHealthDice = function (args) {
        this.kotAnimationManager.resolveHealthDice(args.playerId, args.deltaHealth);
        this.diceManager.resolveHealthDice(args.deltaHealth);
    };
    KingOfTokyo.prototype.notif_resolveHealthDiceInTokyo = function (args) {
        this.diceManager.resolveHealthDiceInTokyo();
    };
    KingOfTokyo.prototype.notif_resolveHealingRay = function (args) {
        this.kotAnimationManager.resolveHealthDice(args.healedPlayerId, args.healNumber);
        this.diceManager.resolveHealthDice(args.healNumber);
    };
    KingOfTokyo.prototype.notif_resolveEnergyDice = function (args) {
        this.kotAnimationManager.resolveEnergyDice(args);
        this.diceManager.resolveEnergyDice();
    };
    KingOfTokyo.prototype.notif_resolveSmashDice = function (args) {
        this.kotAnimationManager.resolveSmashDice(args);
        this.diceManager.resolveSmashDice();
        if (args.smashedPlayersIds.length > 0) {
            for (var delayIndex = 0; delayIndex < args.number; delayIndex++) {
                setTimeout(function () { return playSound('kot-punch'); }, ANIMATION_MS - (PUNCH_SOUND_DURATION * delayIndex - 1));
            }
        }
    };
    KingOfTokyo.prototype.notif_playerEliminated = function (args) {
        var playerId = Number(args.who_quits);
        this.setPoints(playerId, 0);
        this.eliminatePlayer(playerId);
    };
    KingOfTokyo.prototype.notif_kotPlayerEliminated = function (args) {
        this.notif_playerEliminated(args);
    };
    KingOfTokyo.prototype.notif_leaveTokyo = function (args) {
        this.getPlayerTable(args.playerId).leaveTokyo();
        dojo.removeClass("overall_player_board_".concat(args.playerId), 'intokyo');
        dojo.removeClass("monster-board-wrapper-".concat(args.playerId), 'intokyo');
        if (args.playerId == this.getPlayerId()) {
            this.removeAutoLeaveUnderButton();
        }
        if (this.smashedPlayersStillInTokyo) {
            this.smashedPlayersStillInTokyo = this.smashedPlayersStillInTokyo.filter(function (playerId) { return playerId != args.playerId; });
        }
        var useChestThumpingButton = document.getElementById("useChestThumping_button".concat(args.playerId));
        useChestThumpingButton === null || useChestThumpingButton === void 0 ? void 0 : useChestThumpingButton.parentElement.removeChild(useChestThumpingButton);
    };
    KingOfTokyo.prototype.notif_playerEntersTokyo = function (args) {
        this.getPlayerTable(args.playerId).enterTokyo(args.location);
        dojo.addClass("overall_player_board_".concat(args.playerId), 'intokyo');
        dojo.addClass("monster-board-wrapper-".concat(args.playerId), 'intokyo');
        if (args.playerId == this.getPlayerId()) {
            this.addAutoLeaveUnderButton();
        }
    };
    KingOfTokyo.prototype.notif_buyCard = function (args) {
        var _this = this;
        var card = args.card;
        var playerId = args.playerId;
        var playerTable = this.getPlayerTable(playerId);
        if (args.energy !== undefined) {
            this.setEnergy(playerId, args.energy);
        }
        if (args.discardCard) { // initial card
            playerTable.cards.addCard(card, { fromStock: this.tableCenter.getVisibleCards() });
        }
        else if (args.newCard) {
            var newCard_1 = args.newCard;
            playerTable.cards.addCard(card, { fromStock: this.tableCenter.getVisibleCards() }).then(function () {
                _this.tableCenter.getVisibleCards().addCard(newCard_1, { fromElement: document.getElementById('deck'), originalSide: 'back', rotationDelta: 90 });
            });
        }
        else if (args.from > 0) {
            var fromStock = args.from == playerId ? playerTable.reservedCards : this.getPlayerTable(args.from).cards;
            playerTable.cards.addCard(card, { fromStock: fromStock });
        }
        else { // from Made in a lab Pick
            var settings = this.tableCenter.getPickCard() ? // active player
                { fromStock: this.tableCenter.getPickCard() } :
                { fromElement: document.getElementById('deck'), originalSide: 'back', rotationDelta: 90 };
            playerTable.cards.addCard(card, settings);
        }
        //this.cardsManager.settings.setupFrontDiv(card, this.cardsManager.getCardElement(card).getElementsByClassName('front')[0]);
        if (card.tokens) {
            this.cardsManager.placeTokensOnCard(card, playerId);
        }
        this.tableCenter.setTopDeckCard(args.topDeckCard, args.deckCardsCount);
        this.tableManager.tableHeightChange(); // adapt to new card
    };
    KingOfTokyo.prototype.notif_reserveCard = function (args) {
        var card = args.card;
        var newCard = args.newCard;
        this.getPlayerTable(args.playerId).reservedCards.addCard(card, { fromStock: this.tableCenter.getVisibleCards() }); // TODOPUBG add under evolution
        this.tableCenter.getVisibleCards().addCard(newCard, { fromElement: document.getElementById('deck'), originalSide: 'back', rotationDelta: 90 });
        this.tableCenter.setTopDeckCard(args.topDeckCard, args.deckCardsCount);
        this.tableManager.tableHeightChange(); // adapt to new card
    };
    KingOfTokyo.prototype.notif_removeCards = function (args) {
        var _this = this;
        if (args.delay) {
            args.delay = false;
            setTimeout(function () { return _this.notif_removeCards(args); }, ANIMATION_MS);
        }
        else {
            this.getPlayerTable(args.playerId).removeCards(args.cards);
            this.tableManager.tableHeightChange(); // adapt after removed cards
        }
    };
    KingOfTokyo.prototype.notif_removeEvolutions = function (args) {
        var _this = this;
        if (args.delay) {
            setTimeout(function () { return _this.notif_removeEvolutions(__assign(__assign({}, args), { delay: 0 })); }, args.delay);
        }
        else {
            this.getPlayerTable(args.playerId).removeEvolutions(args.cards);
            this.handCounters[args.playerId].incValue(-args.cards.filter(function (card) { return card.location === 'hand'; }).length);
            this.tableManager.tableHeightChange(); // adapt after removed cards
        }
    };
    KingOfTokyo.prototype.notif_setMimicToken = function (args) {
        this.setMimicToken(args.type, args.card);
    };
    KingOfTokyo.prototype.notif_removeMimicToken = function (args) {
        this.removeMimicToken(args.type, args.card);
    };
    KingOfTokyo.prototype.notif_removeMimicEvolutionToken = function (args) {
        this.removeMimicEvolutionToken(args.card);
    };
    KingOfTokyo.prototype.notif_setMimicEvolutionToken = function (args) {
        this.setMimicEvolutionToken(args.card);
    };
    KingOfTokyo.prototype.notif_renewCards = function (args) {
        this.setEnergy(args.playerId, args.energy);
        return this.tableCenter.renewCards(args.cards, args.topDeckCard, args.deckCardsCount);
    };
    KingOfTokyo.prototype.notif_points = function (args) {
        this.setPoints(args.playerId, args.points);
    };
    KingOfTokyo.prototype.notif_health = function (args) {
        this.setHealth(args.playerId, args.health);
        /*const rapidHealingSyncButton = document.getElementById('rapidHealingSync_button');
        if (rapidHealingSyncButton && args.playerId === this.getPlayerId()) {
            this.rapidHealingSyncHearts = Math.max(0, this.rapidHealingSyncHearts - args.delta_health);
            rapidHealingSyncButton.innerHTML = dojo.string.substitute(_("Use ${card_name}") + " : " + formatTextIcons(`${_('Gain ${hearts}[Heart]')} (${2*this.rapidHealingSyncHearts}[Energy])`), { 'card_name': this.cards.getCardName(37, 'text-only'), 'hearts': this.rapidHealingSyncHearts });
        }*/
    };
    KingOfTokyo.prototype.notif_maxHealth = function (args) {
        this.setMaxHealth(args.playerId, args.maxHealth);
        this.setHealth(args.playerId, args.health);
    };
    KingOfTokyo.prototype.notif_energy = function (args) {
        this.setEnergy(args.playerId, args.energy);
    };
    KingOfTokyo.prototype.notif_wickedness = function (args) {
        this.setWickedness(args.playerId, args.wickedness);
    };
    KingOfTokyo.prototype.notif_shrinkRayToken = function (args) {
        this.setShrinkRayTokens(args.playerId, args.tokens);
    };
    KingOfTokyo.prototype.notif_poisonToken = function (args) {
        this.setPoisonTokens(args.playerId, args.tokens);
    };
    KingOfTokyo.prototype.notif_removeShrinkRayToken = function (args) {
        var _this = this;
        this.kotAnimationManager.resolveHealthDice(args.playerId, args.deltaTokens, 'shrink-ray');
        this.diceManager.resolveHealthDice(args.deltaTokens);
        setTimeout(function () { return _this.notif_shrinkRayToken(args); }, ANIMATION_MS);
    };
    KingOfTokyo.prototype.notif_removePoisonToken = function (args) {
        var _this = this;
        this.kotAnimationManager.resolveHealthDice(args.playerId, args.deltaTokens, 'poison');
        this.diceManager.resolveHealthDice(args.deltaTokens);
        setTimeout(function () { return _this.notif_poisonToken(args); }, ANIMATION_MS);
    };
    KingOfTokyo.prototype.notif_setCardTokens = function (args) {
        this.cardsManager.placeTokensOnCard(args.card, args.playerId);
    };
    KingOfTokyo.prototype.notif_setEvolutionTokens = function (args) {
        this.evolutionCardsManager.placeTokensOnCard(args.card, args.playerId);
    };
    KingOfTokyo.prototype.notif_setTileTokens = function (args) {
        this.wickednessTilesManager.placeTokensOnTile(args.card, args.playerId);
    };
    KingOfTokyo.prototype.notif_toggleRapidHealing = function (args) {
        if (args.active) {
            this.addRapidHealingButton(args.playerEnergy, args.isMaxHealth);
        }
        else {
            this.removeRapidHealingButton();
        }
    };
    KingOfTokyo.prototype.notif_toggleMothershipSupport = function (args) {
        if (args.active) {
            this.addMothershipSupportButton(args.playerEnergy, args.isMaxHealth);
        }
        else {
            this.removeMothershipSupportButton();
        }
    };
    KingOfTokyo.prototype.notif_toggleMothershipSupportUsed = function (args) {
        this.gamedatas.players[args.playerId].mothershipSupportUsed = args.used;
        this.checkMothershipSupportButtonState();
    };
    KingOfTokyo.prototype.notif_useCamouflage = function (args) {
        this.notif_updateCancelDamage(args);
        this.diceManager.showCamouflageRoll(args.diceValues);
    };
    KingOfTokyo.prototype.notif_updateCancelDamage = function (args) {
        if (args.cancelDamageArgs) {
            this.gamedatas.gamestate.args = args.cancelDamageArgs;
            this.updatePageTitle();
            this.onEnteringCancelDamage(args.cancelDamageArgs, this.isCurrentPlayerActive());
        }
    };
    KingOfTokyo.prototype.notif_useLightningArmor = function (args) {
        this.diceManager.showCamouflageRoll(args.diceValues);
    };
    KingOfTokyo.prototype.notif_changeDie = function (args) {
        if (args.psychicProbeRollDieArgs) {
            this.onEnteringPsychicProbeRollDie(args.psychicProbeRollDieArgs);
        }
        else {
            this.diceManager.changeDie(args.dieId, args.canHealWithDice, args.toValue, args.roll);
        }
    };
    KingOfTokyo.prototype.notif_rethrow3changeDie = function (args) {
        this.diceManager.changeDie(args.dieId, args.canHealWithDice, args.toValue, args.roll);
    };
    KingOfTokyo.prototype.notif_changeDice = function (args) {
        var _this = this;
        Object.keys(args.dieIdsToValues).forEach(function (key) {
            return _this.diceManager.changeDie(Number(key), args.canHealWithDice, args.dieIdsToValues[key], false);
        });
    };
    KingOfTokyo.prototype.notif_resolvePlayerDice = function () {
        this.diceManager.lockAll();
    };
    KingOfTokyo.prototype.notif_updateLeaveTokyoUnder = function (args) {
        dojo.query('.autoLeaveButton').removeClass('bgabutton_blue');
        dojo.query('.autoLeaveButton').addClass('bgabutton_gray');
        var popinId = "discussion_bubble_autoLeaveUnder";
        if (document.getElementById("".concat(popinId, "_set").concat(args.under))) {
            dojo.removeClass("".concat(popinId, "_set").concat(args.under), 'bgabutton_gray');
            dojo.addClass("".concat(popinId, "_set").concat(args.under), 'bgabutton_blue');
        }
        for (var i = 1; i <= 15; i++) {
            if (document.getElementById("".concat(popinId, "_setStay").concat(i))) {
                dojo.toggleClass("".concat(popinId, "_setStay").concat(i), 'disabled', args.under > 0 && i <= args.under);
            }
        }
    };
    KingOfTokyo.prototype.notif_updateStayTokyoOver = function (args) {
        dojo.query('.autoStayButton').removeClass('bgabutton_blue');
        dojo.query('.autoStayButton').addClass('bgabutton_gray');
        var popinId = "discussion_bubble_autoLeaveUnder";
        if (document.getElementById("".concat(popinId, "_setStay").concat(args.over))) {
            dojo.removeClass("".concat(popinId, "_setStay").concat(args.over), 'bgabutton_gray');
            dojo.addClass("".concat(popinId, "_setStay").concat(args.over), 'bgabutton_blue');
        }
    };
    KingOfTokyo.prototype.notif_updateAskPlayEvolution = function (args) {
        var input = document.querySelector("input[name=\"autoSkipPlayEvolution\"][value=\"".concat(args.value, "\"]"));
        if (input) {
            input.checked = true;
        }
    };
    KingOfTokyo.prototype.notif_changeTokyoTowerOwner = function (args) {
        var playerId = args.playerId;
        var previousOwner = this.towerLevelsOwners[args.level];
        this.towerLevelsOwners[args.level] = playerId;
        var newLevelTower = playerId == 0 ? this.tableCenter.getTokyoTower() : this.getPlayerTable(playerId).getTokyoTower();
        transitionToObjectAndAttach(this, document.getElementById("tokyo-tower-level".concat(args.level)), "".concat(newLevelTower.divId, "-level").concat(args.level), this.getZoom());
        if (previousOwner != 0) {
            document.getElementById("tokyo-tower-icon-".concat(previousOwner, "-level-").concat(args.level)).dataset.owned = 'false';
        }
        if (playerId != 0) {
            document.getElementById("tokyo-tower-icon-".concat(playerId, "-level-").concat(args.level)).dataset.owned = 'true';
        }
    };
    KingOfTokyo.prototype.notif_setPlayerBerserk = function (args) {
        this.getPlayerTable(args.playerId).setBerserk(args.berserk);
        dojo.toggleClass("player-panel-berserk-".concat(args.playerId), 'active', args.berserk);
    };
    KingOfTokyo.prototype.notif_changeForm = function (args) {
        this.getPlayerTable(args.playerId).changeForm(args.card);
        this.setEnergy(args.playerId, args.energy);
    };
    KingOfTokyo.prototype.notif_cultist = function (args) {
        this.setCultists(args.playerId, args.cultists, args.isMaxHealth);
    };
    KingOfTokyo.prototype.notif_changeCurseCard = function (args) {
        return this.tableCenter.changeCurseCard(args.card, args.hiddenCurseCardCount, args.topCurseDeckCard);
    };
    KingOfTokyo.prototype.notif_takeWickednessTile = function (args) {
        var tile = args.tile;
        this.getPlayerTable(args.playerId).wickednessTiles.addCard(tile, {
            fromStock: this.tableCenter.wickednessDecks.getStock(tile)
        });
        this.tableCenter.removeWickednessTileFromPile(args.level, tile);
        this.tableManager.tableHeightChange(); // adapt to new card
    };
    KingOfTokyo.prototype.notif_removeWickednessTiles = function (args) {
        this.getPlayerTable(args.playerId).removeWickednessTiles(args.tiles);
        this.tableManager.tableHeightChange(); // adapt after removed cards
    };
    KingOfTokyo.prototype.notif_changeGoldenScarabOwner = function (args) {
        this.getPlayerTable(args.playerId).takeGoldenScarab();
        this.tableManager.tableHeightChange(); // adapt after moved card
    };
    KingOfTokyo.prototype.notif_discardedDie = function (args) {
        this.diceManager.discardDie(args.die);
    };
    KingOfTokyo.prototype.notif_exchangeCard = function (args) {
        var previousOwnerCards = this.getPlayerTable(args.previousOwner).cards;
        var playerCards = this.getPlayerTable(args.playerId).cards;
        previousOwnerCards.addCard(args.unstableDnaCard, { fromStock: playerCards });
        playerCards.addCard(args.exchangedCard, { fromStock: playerCards });
    };
    KingOfTokyo.prototype.notif_addEvolutionCardInHand = function (args) {
        var playerId = args.playerId;
        var card = args.card;
        var isCurrentPlayer = this.getPlayerId() === playerId;
        var playerTable = this.getPlayerTable(playerId);
        if (isCurrentPlayer) {
            if (card === null || card === void 0 ? void 0 : card.type) {
                playerTable.hiddenEvolutionCards.addCard(card);
            }
        }
        else if (card === null || card === void 0 ? void 0 : card.id) {
            playerTable.hiddenEvolutionCards.addCard(card);
        }
        if (!card || !card.type) {
            this.handCounters[playerId].incValue(1);
        }
        playerTable === null || playerTable === void 0 ? void 0 : playerTable.checkHandEmpty();
        this.tableManager.tableHeightChange(); // adapt to new card
    };
    KingOfTokyo.prototype.notif_playEvolution = function (args) {
        this.handCounters[args.playerId].incValue(-1);
        var fromStock = null;
        if (args.fromPlayerId) {
            fromStock = this.getPlayerTable(args.fromPlayerId).visibleEvolutionCards;
        }
        this.getPlayerTable(args.playerId).playEvolution(args.card, fromStock);
        if (args.fromPlayerId) {
            this.getPlayerTable(args.fromPlayerId).visibleEvolutionCards.removeCard(args.card);
        }
        this.tableManager.tableHeightChange(); // adapt to new card
    };
    KingOfTokyo.prototype.notif_addSuperiorAlienTechnologyToken = function (args) {
        this.cardsManager.placeSuperiorAlienTechnologyTokenOnCard(args.card);
    };
    KingOfTokyo.prototype.notif_giveTarget = function (args) {
        if (args.previousOwner) {
            this.getPlayerTable(args.previousOwner).removeTarget();
            this.setPlayerTokens(args.previousOwner, 0, 'target');
        }
        this.getPlayerTable(args.playerId).giveTarget();
        this.setPlayerTokens(args.playerId, 1, 'target');
    };
    KingOfTokyo.prototype.notif_ownedEvolutions = function (args) {
        this.gamedatas.players[args.playerId].ownedEvolutions = args.evolutions;
    };
    KingOfTokyo.prototype.setTitleBarSuperiorAlienTechnologyCard = function (card, parent) {
        var _this = this;
        if (parent === void 0) { parent = "maintitlebar_content"; }
        dojo.place("<div id=\"title-bar-stock\" class=\"card-in-title-wrapper\"></div>", parent);
        this.titleBarStock = new LineStock(this.cardsManager, document.getElementById('title-bar-stock'));
        this.titleBarStock.addCard(__assign(__assign({}, card), { id: 9999 + card.id }));
        this.titleBarStock.setSelectionMode('single');
        this.titleBarStock.onCardClick = function () { return _this.throwDieSuperiorAlienTechnology(); };
    };
    KingOfTokyo.prototype.notif_superiorAlienTechnologyRolledDie = function (args) {
        this.setTitleBarSuperiorAlienTechnologyCard(args.card, 'gameaction_status_wrap');
        this.setDiceSelectorVisibility(true);
        this.diceManager.showCamouflageRoll([{
                id: 0,
                value: args.dieValue,
                extra: false,
                locked: false,
                rolled: true,
                type: 0,
                canReroll: true,
            }]);
    };
    KingOfTokyo.prototype.notif_superiorAlienTechnologyLog = function (args) {
        //this.setTitleBarSuperiorAlienTechnologyCard(args.card, 'gameaction_status_wrap');
        if (document.getElementById('dice0')) {
            var message = args.dieValue == 6 ?
                _('<strong>${card_name}</strong> card removed!') :
                _('<strong>${card_name}</strong> card kept!');
            this.doShowBubble('dice0', dojo.string.substitute(message, {
                'card_name': this.cardsManager.getCardName(args.card.type, 'text-only')
            }), 'superiorAlienTechnologyBubble');
        }
    };
    KingOfTokyo.prototype.notif_resurrect = function (args) {
        if (args.zombified) {
            this.getPlayerTable(args.playerId).zombify();
        }
    };
    KingOfTokyo.prototype.notif_mindbugPlayer = function (args) {
        var _a, _b;
        if (args.mindbuggedPlayerId) {
            // start of mindbug
            document.getElementById('rolled-dice-and-rapid-actions').insertAdjacentHTML('afterend', "\n                <div id=\"mindbug-notice\">\n                    ".concat(
            /*_TODOMB*/ ('${player_name} mindbugs the turn of ${player_name2}')
                .replace('${player_name}', this.getFormattedPlayerName(args.activePlayerId))
                .replace('${player_name2}', this.getFormattedPlayerName(args.mindbuggedPlayerId)), "\n                </div>\n            "));
            document.getElementById("player-table-".concat(args.mindbuggedPlayerId)).classList.add('mindbugged');
        }
        else {
            // end of mindbug
            (_a = document.querySelector('.player-table.mindbugged')) === null || _a === void 0 ? void 0 : _a.classList.remove('mindbugged');
            (_b = document.getElementById('mindbug-notice')) === null || _b === void 0 ? void 0 : _b.remove();
        }
    };
    KingOfTokyo.prototype.notif_setPlayerCounter = function (args) {
        var name = args.name, playerId = args.playerId, value = args.value;
        if (name === 'mindbugTokens') {
            this.setPlayerTokens(playerId, value, 'mindbug');
        }
    };
    KingOfTokyo.prototype.setPoints = function (playerId, points, delay) {
        var _a;
        if (delay === void 0) { delay = 0; }
        (_a = this.scoreCtrl[playerId]) === null || _a === void 0 ? void 0 : _a.toValue(points);
        this.getPlayerTable(playerId).setPoints(points, delay);
    };
    KingOfTokyo.prototype.setHealth = function (playerId, health, delay) {
        if (delay === void 0) { delay = 0; }
        this.healthCounters[playerId].toValue(health);
        this.getPlayerTable(playerId).setHealth(health, delay);
        this.checkRapidHealingButtonState();
        this.checkMothershipSupportButtonState();
        this.checkHealthCultistButtonState();
    };
    KingOfTokyo.prototype.setMaxHealth = function (playerId, maxHealth) {
        this.gamedatas.players[playerId].maxHealth = maxHealth;
        this.checkRapidHealingButtonState();
        this.checkMothershipSupportButtonState();
        this.checkHealthCultistButtonState();
        var popinId = "discussion_bubble_autoLeaveUnder";
        if (document.getElementById(popinId)) {
            this.updateAutoLeavePopinButtons();
        }
    };
    KingOfTokyo.prototype.getPlayerHealth = function (playerId) {
        return this.healthCounters[playerId].getValue();
    };
    KingOfTokyo.prototype.getPlayerEnergy = function (playerId) {
        return this.energyCounters[playerId].getValue();
    };
    KingOfTokyo.prototype.setEnergy = function (playerId, energy, delay) {
        if (delay === void 0) { delay = 0; }
        this.energyCounters[playerId].toValue(energy);
        this.getPlayerTable(playerId).setEnergy(energy, delay);
        this.checkBuyEnergyDrinkState(energy); // disable button if energy gets down to 0
        this.checkRapidHealingButtonState();
        this.checkMothershipSupportButtonState();
        this.setBuyDisabledCard(null, energy);
        this.updateEnableAtEnergy(playerId, energy);
    };
    KingOfTokyo.prototype.updateEnableAtEnergy = function (playerId, energy) {
        if (energy === void 0) { energy = null; }
        if (energy === null) {
            energy = this.getPlayerEnergy(playerId);
        }
        Array.from(document.querySelectorAll("[data-enable-at-energy]")).forEach(function (button) {
            var enableAtEnergy = Number(button.dataset.enableAtEnergy);
            button.classList.toggle('disabled', energy < enableAtEnergy);
        });
    };
    KingOfTokyo.prototype.setWickedness = function (playerId, wickedness) {
        this.wickednessCounters[playerId].toValue(wickedness);
        this.tableCenter.setWickedness(playerId, wickedness);
    };
    KingOfTokyo.prototype.setPlayerTokens = function (playerId, tokens, tokenName) {
        var containerId = "player-board-".concat(tokenName, "-tokens-").concat(playerId);
        var container = document.getElementById(containerId);
        while (container.childElementCount > tokens) {
            container.removeChild(container.lastChild);
        }
        for (var i = container.childElementCount; i < tokens; i++) {
            dojo.place("<div class=\"".concat(tokenName, " token\"></div>"), containerId);
        }
    };
    KingOfTokyo.prototype.setShrinkRayTokens = function (playerId, tokens) {
        var _a;
        this.setPlayerTokens(playerId, tokens, 'shrink-ray');
        (_a = this.getPlayerTable(playerId)) === null || _a === void 0 ? void 0 : _a.setShrinkRayTokens(tokens);
    };
    KingOfTokyo.prototype.setPoisonTokens = function (playerId, tokens) {
        var _a;
        this.setPlayerTokens(playerId, tokens, 'poison');
        (_a = this.getPlayerTable(playerId)) === null || _a === void 0 ? void 0 : _a.setPoisonTokens(tokens);
    };
    KingOfTokyo.prototype.setCultists = function (playerId, cultists, isMaxHealth) {
        var _a;
        this.cultistCounters[playerId].toValue(cultists);
        (_a = this.getPlayerTable(playerId)) === null || _a === void 0 ? void 0 : _a.setCultistTokens(cultists);
        if (playerId == this.getPlayerId()) {
            if (cultists > 0) {
                this.addRapidCultistButtons(isMaxHealth);
            }
            else {
                this.removeRapidCultistButtons();
                if (document.getElementById('use_cultist_button')) {
                    dojo.addClass('use_cultist_button', 'disabled');
                }
            }
        }
    };
    KingOfTokyo.prototype.checkBuyEnergyDrinkState = function (energy) {
        if (energy === void 0) { energy = null; }
        if (document.getElementById('buy_energy_drink_button')) {
            if (energy === null) {
                energy = this.energyCounters[this.getPlayerId()].getValue();
            }
            dojo.toggleClass('buy_energy_drink_button', 'disabled', energy < 1 || !this.diceManager.canRethrow());
        }
    };
    KingOfTokyo.prototype.checkUseSmokeCloudState = function () {
        if (document.getElementById('use_smoke_cloud_button')) {
            dojo.toggleClass('use_smoke_cloud_button', 'disabled', !this.diceManager.canRethrow());
        }
    };
    KingOfTokyo.prototype.checkUseCultistState = function () {
        if (document.getElementById('use_cultist_button')) {
            dojo.toggleClass('use_cultist_button', 'disabled', !this.diceManager.canRethrow());
        }
    };
    KingOfTokyo.prototype.eliminatePlayer = function (playerId) {
        this.gamedatas.players[playerId].eliminated = 1;
        document.getElementById("overall_player_board_".concat(playerId)).classList.add('eliminated-player');
        if (!document.getElementById("dead-icon-".concat(playerId))) {
            dojo.place("<div id=\"dead-icon-".concat(playerId, "\" class=\"icon dead\"></div>"), "player_board_".concat(playerId));
        }
        this.getPlayerTable(playerId).eliminatePlayer();
        this.tableManager.tableHeightChange(); // because all player's card were removed
        if (document.getElementById("player-board-monster-figure-".concat(playerId))) {
            this.fadeOutAndDestroy("player-board-monster-figure-".concat(playerId));
        }
        dojo.removeClass("overall_player_board_".concat(playerId), 'intokyo');
        dojo.removeClass("monster-board-wrapper-".concat(playerId), 'intokyo');
        if (playerId == this.getPlayerId()) {
            this.removeAutoLeaveUnderButton();
        }
        this.setShrinkRayTokens(playerId, 0);
        this.setPlayerTokens(playerId, 0, 'mindbug');
        this.setPoisonTokens(playerId, 0);
        if (this.isCthulhuExpansion()) {
            this.setCultists(playerId, 0, false);
        }
    };
    KingOfTokyo.prototype.getLogCardName = function (logType) {
        if (logType >= 3000) {
            return this.evolutionCardsManager.getCardName(logType - 3000, 'text-only');
        }
        else if (logType >= 2000) {
            return this.wickednessTilesManager.getCardName(logType - 2000);
        }
        else if (logType >= 1000) {
            return this.curseCardsManager.getCardName(logType - 1000);
        }
        else {
            return this.cardsManager.getCardName(logType, 'text-only');
        }
    };
    KingOfTokyo.prototype.getLogCardTooltip = function (logType) {
        if (logType >= 3000) {
            return this.evolutionCardsManager.getTooltip(logType - 3000);
        }
        else if (logType >= 2000) {
            return this.wickednessTilesManager.getTooltip(logType - 2000);
        }
        else if (logType >= 1000) {
            return this.curseCardsManager.getTooltip(logType - 1000);
        }
        else {
            return this.cardsManager.getTooltip(logType);
        }
    };
    /* This enable to inject translatable styled things to logs or action bar */
    /* @Override */
    KingOfTokyo.prototype.format_string_recursive = function (log, args) {
        var _this = this;
        var _a, _b;
        try {
            if (log && args && !args.processed) {
                // Representation of the color of a card
                ['card_name', 'card_name2'].forEach(function (cardArg) {
                    if (args[cardArg]) {
                        var types = null;
                        if (typeof args[cardArg] == 'number') {
                            types = [args[cardArg]];
                        }
                        else if (typeof args[cardArg] == 'string' && args[cardArg][0] >= '0' && args[cardArg][0] <= '9') {
                            types = args[cardArg].split(',').map(function (cardType) { return Number(cardType); });
                        }
                        if (types !== null) {
                            var tags = types.map(function (cardType) {
                                var cardLogId = _this.cardLogId++;
                                setTimeout(function () { return _this.addTooltipHtml("card-log-".concat(cardLogId), _this.getLogCardTooltip(cardType)); }, 500);
                                return "<strong id=\"card-log-".concat(cardLogId, "\" data-log-type=\"").concat(cardType, "\">").concat(_this.getLogCardName(cardType), "</strong>");
                            });
                            args[cardArg] = tags.join(', ');
                        }
                    }
                });
                for (var property in args) {
                    if (((_b = (_a = args[property]) === null || _a === void 0 ? void 0 : _a.indexOf) === null || _b === void 0 ? void 0 : _b.call(_a, ']')) > 0) {
                        args[property] = formatTextIcons(_(args[property]));
                    }
                }
                if (args.player_name && typeof args.player_name[0] === 'string' && args.player_name.indexOf('<') === -1) {
                    var player = Object.values(this.gamedatas.players).find(function (player) { return player.name == args.player_name; });
                    args.player_name = "<span style=\"font-weight:bold;color:#".concat(player.color, ";\">").concat(args.player_name, "</span>");
                }
                if (args.symbolsToGive && typeof args.symbolsToGive === 'object') {
                    var symbolsStr = args.symbolsToGive.map(function (symbol) { return SYMBOL_AS_STRING_PADDED[symbol]; });
                    args.symbolsToGive = formatTextIcons(_('${symbol1} or ${symbol2}')
                        .replace('${symbol1}', symbolsStr.slice(0, symbolsStr.length - 1).join(', '))
                        .replace('${symbol2}', symbolsStr[symbolsStr.length - 1]));
                }
                log = formatTextIcons(_(log));
            }
        }
        catch (e) {
            console.error(log, args, "Exception thrown", e.stack);
        }
        return this.inherited(arguments);
    };
    return KingOfTokyo;
}(GameGui));
define([
    "dojo", "dojo/_base/declare",
    "ebg/core/gamegui",
    "ebg/counter",
    "ebg/stock"
], function (dojo, declare) {
    return declare("bgagame.kingoftokyo", ebg.core.gamegui, new KingOfTokyo());
});
