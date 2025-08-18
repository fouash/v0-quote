@echo off
echo Updating Getlancer Quote Dependencies...
echo.

echo Step 1: Updating root dependencies...
npm install

echo.
echo Step 2: Updating client dependencies...
cd client
npm install

echo.
echo Step 3: Updating bower dependencies...
bower install

echo.
echo Step 4: Running security audit...
npm audit

echo.
echo Update complete! Please review any security warnings above.
echo.
pause