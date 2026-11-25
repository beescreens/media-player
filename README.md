# media-player

## Debugging PHP

The development container includes Xdebug and the
[PHP Debug](https://marketplace.visualstudio.com/items?itemName=xdebug.php-debug)
extension. Xdebug only starts when triggered, so regular PHP commands are not
affected.

### Debug a command or a web request

1. Set a breakpoint by clicking to the left of a line number.
2. Open **Run and Debug** (`Ctrl+Shift+D`, or `Cmd+Shift+D` on macOS), select
   **Listen for Xdebug**, and press `F5`.
3. Trigger Xdebug:

    - For a command, set the `XDEBUG_TRIGGER` environment variable:

        ```bash
        XDEBUG_TRIGGER=1 php script.php
        ```

    - For a web request, add `XDEBUG_TRIGGER` to the URL:

        ```text
        http://localhost:8000/?XDEBUG_TRIGGER=1
        ```

### Debug the current file

Open a standalone PHP file, select **Debug current PHP file** in **Run and
Debug**, and press `F5`. No trigger is needed.

### Troubleshooting

If `Could not connect to debugging client` is displayed, Xdebug was triggered
but nothing is listening. Start **Listen for Xdebug** first.
