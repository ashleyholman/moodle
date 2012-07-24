var sessionTimeout = sessionTimeout || {};

function watchTimeout (Y, timeout) {
  // amount of time prior to session expiry to alert the user
  var gracePeriod = 300;

  // alert the user after this many seconds
  var alertAfter = timeout - gracePeriod;
  if (alertAfter < 60) {
    // minimum 60 seconds
    alertAfter = 60;
  }
  sessionTimeout.idletimer = setTimeout(alertUser, alertAfter*1000);

  // send keep-alive for editor activity every 10 mins
  var d = new Date();
  var lastEditorKeepalive = sessionTimeout.lastEdit = d.getTime();
  sessionTimeout.editortimer = setInterval(checkEditorActivity, 300000);

  function alertUser() {
    showPanel('Session Expiry Alert',
              'Your login session may be about to expire due to inactivity.  Click "Continue" now to extend your login session.',
              [
                {
                  value  : 'Continue',
                  action : function (e) {
                              extendSession(this, e);
                              e.preventDefault();
                           }
                },
                {
                  value  : 'Ignore',
                  action : function (e) {
                             e.preventDefault();
                             this.hide();
                           }
                }
              ]
    );
  }

  function extendSession(panel, e) {
    e.target.ancestor(function(n) { return n.hasClass('yui3-widget-button-wrapper') }).remove();
    panel.set('bodyContent', 'Attempting to extend session...');
    Y.io(M.cfg.wwwroot + '/session_extend.php?sesskey=' + M.cfg.sesskey, {
    on: {
        complete: function(id, e) {
            var json = JSON.parse(e.responseText);
            panel.hide();
            if (json.result == 'success') {
              lastEditorKeepalive = sessionTimeout.lastEdit;
              notifySuccess();
              sessionTimeout.idletimer = setTimeout(alertUser, alertAfter*1000);
            } else {
              notifyFailure();
            }
        }
    }
    });
  }

  function notifySuccess() {
    showPanel('Session Extended',
              'Your session has been extended successfully.  Click OK to continue working.',
              [
                {
                  value  : 'OK',
                  section: Y.WidgetStdMod.FOOTER,
                  action : function (e) {
                      e.preventDefault();
                      this.hide();
                  }
                }
              ]
    );
  }

  function notifyFailure() {
    Y.use("panel", "dd-plugin", function (Y) {
    showPanel('Session Expired',
              'Your session could not be extended as you are already logged out. ' +
              'If you attempt to submit any work from this page it will be lost.  ' +
              'If you have any unsaved work on this page, copy-and-paste it into ' +
              'a program on your computer so you do not lose it.  You will then ' +
              'need to re-login to Moodle',
              [
                {
                  value  : 'OK',
                  section: Y.WidgetStdMod.FOOTER,
                  action : function (e) {
                      e.preventDefault();
                      this.hide();
                  }
                }
              ]
    );
    });
  }

  function showPanel(headerContent, bodyContent, buttons) {
    Y.use("panel", "dd-plugin", function (Y) {
      for (var i=0;i<buttons.length;i++) {
        buttons[i].section = Y.WidgetStdMod.FOOTER;
      }
      var panel = new Y.Panel({
        headerContent: headerContent,
        bodyContent: bodyContent,
        width      : 400,
        zIndex     : 6,
        centered   : true,
        modal      : true,
        render     : '#panel',
        plugin     : [Y.Plugin.Drag],
        buttons    : buttons
      })
      panel.show();
    });
  }

  function checkEditorActivity() {
    if (sessionTimeout.lastEdit > lastEditorKeepalive) {
      Y.io(M.cfg.wwwroot + '/session_extend.php?sesskey=' + M.cfg.sesskey, {
      on: {
          complete: function(id, e) {
              var json = JSON.parse(e.responseText);
              if (json.result == 'success') {
                lastEditorKeepalive = sessionTimeout.lastEdit;
                // session extended - so reset the idle timer
                clearTimeout(sessionTimeout.idletimer);
                sessionTimeout.idletimer = setTimeout(alertUser, alertAfter*1000);
              } else {
                notifyFailure();
                clearTimeout(sessionTimeout.idletimer);
                clearTimeout(sessionTimeout.editortimer);
              }
          }
      }});
    }
  } 
}

// this function is called by TinyMCE when the editor contents change
function sessionTimeoutEditorContentsChanged(inst) {
  sessionTimeout.lastEdit = new Date().getTime();
}
