from selenium import webdriver
from selenium.webdriver.chrome.service import Service
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from webdriver_manager.chrome import ChromeDriverManager
import time

# Setup Chrome driver
print("Setting up Chrome WebDriver...")
service = Service(ChromeDriverManager().install())
driver = webdriver.Chrome(service=service)

try:
    # STEP 1: Go to UIURP home page
    print("\nSTEP 1: Going to UIURP Home page")
    driver.get("http://localhost:8000/uiurp/")
    print("Page Title:", driver.title)
    print("Current URL:", driver.current_url)
    time.sleep(2)

    # STEP 2: Navigate to Research page
    print("\nSTEP 2: Navigating to Research Page")
    try:
        research_link = WebDriverWait(driver, 10).until(
            EC.element_to_be_clickable((By.XPATH, '//a[contains(@href, "Research_page.php")]'))
        )
        research_link.click()
        print("Clicked Research Page link")
    except:
        print("Could not find Research Page link, trying direct navigation")
        driver.get("http://localhost:8000/uiurp/Research_page.php")
    
    time.sleep(3)
    print("Current URL:", driver.current_url)
    print("Page Title:", driver.title)

    # STEP 3: Try to view Faculty page
    print("\nSTEP 3: Navigating to Faculty Page")
    try:
        faculty_link = WebDriverWait(driver, 10).until(
            EC.element_to_be_clickable((By.XPATH, '//a[contains(@href, "Faculty_Page.php")]'))
        )
        faculty_link.click()
        print("Clicked Faculty Page link")
    except:
        print("Could not find Faculty Page link, trying direct navigation")
        driver.get("http://localhost:8000/uiurp/Faculty_Page.php")
    
    time.sleep(3)
    print("Current URL:", driver.current_url)
    print("Page Title:", driver.title)

    # STEP 4: Check if we can view Forum/Posts
    print("\nSTEP 4: Trying to view Forum/Posts")
    try:
        forum_link = WebDriverWait(driver, 10).until(
            EC.element_to_be_clickable((By.XPATH, '//a[contains(@href, "forum") or contains(@href, "posts") or contains(text(), "Forum")]'))
        )
        forum_link.click()
        print("Clicked Forum link")
    except:
        print("Could not find Forum link, trying direct navigation")
        driver.get("http://localhost:8000/uiurp/view_posts.php")
    
    time.sleep(3)
    print("Current URL:", driver.current_url)
    print("Page Title:", driver.title)
    
    # Check if we were redirected to login
    if "login.php" in driver.current_url:
        print("We were redirected to the login page - forum requires login")
    else:
        print("We can access the forum without login")

    # STEP 5: Try to view a research project (this might require login)
    print("\nSTEP 5: Navigating back to Research Page")
    driver.get("http://localhost:8000/uiurp/Research_page.php")
    time.sleep(3)
    
    try:
        project_links = driver.find_elements(By.XPATH, '//a[contains(@href, "Project_details.php")]')
        if project_links:
            print(f"Found {len(project_links)} project links")
            # Click the first project link
            project_links[0].click()
            print("Clicked on first project link")
            time.sleep(3)
            print("Current URL:", driver.current_url)
            print("Page Title:", driver.title)
            
            # Check if we were redirected to login
            if "login.php" in driver.current_url:
                print("We were redirected to the login page - project details requires login")
            else:
                print("We can access project details without login")
        else:
            print("No project links found")
    except Exception as e:
        print(f"Error trying to access projects: {str(e)}")
    
    # STEP 6: Try to access login page directly
    print("\nSTEP 6: Navigating to Login Page")
    driver.get("http://localhost:8000/uiurp/login.php")
    time.sleep(3)
    print("Current URL:", driver.current_url)
    print("Page Title:", driver.title)
    
    # Take a screenshot for verification
    screenshot_path = "login_page_screenshot.png"
    driver.save_screenshot(screenshot_path)
    print(f"Screenshot saved to {screenshot_path}")

finally:
    # Wait before closing browser
    print("\nTest completed. Waiting for 5 seconds before closing browser...")
    time.sleep(5)
    driver.quit() 