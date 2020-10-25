# NINA-to-XMPP

Since the NINA app doesn't work on my smartphone, I had to develop an alternative.
Since I am permanently available via XMPP anyway, I parse the same JSON files as the NINA app,
check for changes and send an XMPP message if necessary.

You have to install the [mod_post_msg](https://modules.prosody.im/mod_post_msg.html) in your
[prosody](https://prosody.im/).

Then copy config.defaults.php to config.php and fix the values.
