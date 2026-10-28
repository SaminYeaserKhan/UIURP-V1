from selenium import webdriver
from selenium.webdriver.chrome.service import Service
from webdriver_manager.chrome import ChromeDriverManager
import time

# Setup Chrome driver
print("Setting up Chrome WebDriver...")
service = Service(ChromeDriverManager().install())
driver = webdriver.Chrome(service=service)

try:
    # Go to home page
    print("Going to UIURP home page...")
    driver.get("http://localhost:8000/uiurp/")
    time.sleep(3)
    
    # Get page title and URL
    print("Page title:", driver.title)
    print("Current URL:", driver.current_url)
    
    # Print page source length
    source = driver.page_source
    print(f"Page source length: {len(source)} characters")
    
    # Check if we're redirected to login page or on the home page
    if "login.php" in driver.current_url:
        print("We were redirected to the login page")
    else:
        print("We're on the home/index page")
    
    # Take a screenshot for verification
    screenshot_path = "uiurp_screenshot.png"
    driver.save_screenshot(screenshot_path)
    print(f"Screenshot saved to {screenshot_path}")
    
finally:
    # Close the browser
    print("Test completed. Closing browser...")
    time.sleep(3)
    driver.quit() 