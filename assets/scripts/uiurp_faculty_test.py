from selenium import webdriver
from selenium.webdriver.chrome.service import Service
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from webdriver_manager.chrome import ChromeDriverManager
import time
from selenium.common.exceptions import TimeoutException, NoSuchElementException

# Setup Chrome driver
print("Setting up Chrome WebDriver...")
service = Service(ChromeDriverManager().install())
driver = webdriver.Chrome(service=service)

try:
    # STEP 1: Go to UIURP login page
    print("\nSTEP 1: Going to UIURP Login page")
    driver.get("http://localhost:8000/uiurp/login.php")
    print("Page Title:", driver.title)
    time.sleep(2)

    # STEP 2: Login with faculty credentials
    print("\nSTEP 2: Logging in with faculty account")
    # Find username field and enter faculty credentials from the JSON file
    username_input = driver.find_element(By.NAME, 'username')
    username_input.send_keys('Ahmed')  # Using a faculty account
    
    # Find password field and enter password
    password_input = driver.find_element(By.NAME, 'Password')
    password_input.send_keys('12345')
    
    # Click login button - using XPath to find the button in the login form
    login_button = driver.find_element(By.XPATH, '//div[@class="login"]/form/button')
    login_button.click()
    
    print("Login submitted")
    time.sleep(3)
    
    print("Current URL:", driver.current_url)
    print("Page Title:", driver.title)

    # STEP 3: Navigate to Faculty Profile page
    print("\nSTEP 3: Navigating to Faculty Profile Page")
    try:
        profile_link = WebDriverWait(driver, 10).until(
            EC.element_to_be_clickable((By.XPATH, '//a[contains(@href, "Faculty_Profile.php")]'))
        )
        profile_link.click()
        print("Clicked Faculty Profile link")
    except:
        print("Could not find Faculty Profile link, trying direct navigation")
        driver.get("http://localhost:8000/uiurp/Faculty_Profile.php")
    
    time.sleep(3)
    print("Current URL:", driver.current_url)
    print("Page Title:", driver.title)

    # STEP 4: Navigate to Project Management
    print("\nSTEP 4: Navigating to Project Management")
    try:
        project_mgmt_link = WebDriverWait(driver, 10).until(
            EC.element_to_be_clickable((By.XPATH, '//a[contains(@href, "project_management.php")]'))
        )
        project_mgmt_link.click()
        print("Clicked Project Management link")
    except:
        print("Could not find Project Management link, trying direct navigation")
        driver.get("http://localhost:8000/uiurp/project_management.php")
    
    time.sleep(3)
    print("Current URL:", driver.current_url)
    print("Page Title:", driver.title)

    # STEP 5: Navigate to Create New Project tab
    print("\nSTEP 5: Switching to Create New Project tab")
    try:
        new_project_tab = WebDriverWait(driver, 10).until(
            EC.element_to_be_clickable((By.ID, 'new-project-tab'))
        )
        new_project_tab.click()
        print("Clicked on New Project tab")
    except TimeoutException:
        print("Could not find New Project tab")
    
    time.sleep(2)
    
    # STEP 6: Fill out project form (basic fields)
    print("\nSTEP 6: Filling out basic project form fields")
    try:
        # Project title
        title_input = WebDriverWait(driver, 10).until(
            EC.presence_of_element_located((By.ID, 'title'))
        )
        title_input.send_keys("Automated Test Project")
        print("Entered project title")
        
        # Project description
        try:
            desc_input = driver.find_element(By.ID, 'description')
            desc_input.send_keys("This is an automated test project created by Selenium.")
            print("Entered project description")
        except NoSuchElementException:
            print("Could not find description field")
        
        # Project privacy (selecting Public option if available)
        try:
            privacy_toggle = driver.find_element(By.ID, 'privacy-public')
            privacy_toggle.click()
            print("Set project to public")
        except NoSuchElementException:
            print("Could not find privacy toggle")
            
        # Project keywords
        try:
            keywords_input = driver.find_element(By.ID, 'keywords')
            keywords_input.send_keys("testing, automation, selenium")
            print("Entered keywords")
        except NoSuchElementException:
            print("Could not find keywords field")
            
        time.sleep(2)
        
        # Try to submit the form if there's a submit button
        try:
            submit_button = WebDriverWait(driver, 5).until(
                EC.element_to_be_clickable((By.XPATH, '//button[contains(text(), "Create Project") or contains(@type, "submit")]'))
            )
            
            # Just print the message that we found the button, but don't click it to avoid creating test projects
            print("Found submit button but not submitting to avoid creating test projects")
            
            # Uncomment the next line to actually submit the form and create the project
            # submit_button.click()
            # print("Submitted new project form")
            
        except TimeoutException:
            print("Could not find submit button")
        
    except TimeoutException as e:
        print(f"Error filling out form: {str(e)}")
    
    # STEP 7: View Faculty Page
    print("\nSTEP 7: Navigating to Faculty Page")
    try:
        faculty_page_link = WebDriverWait(driver, 10).until(
            EC.element_to_be_clickable((By.XPATH, '//a[contains(@href, "Faculty_Page.php")]'))
        )
        faculty_page_link.click()
        print("Clicked Faculty Page link")
    except:
        print("Could not find Faculty Page link, trying direct navigation")
        driver.get("http://localhost:8000/uiurp/Faculty_Page.php")
    
    time.sleep(3)
    print("Current URL:", driver.current_url)
    print("Page Title:", driver.title)

    # STEP 8: Logout
    print("\nSTEP 8: Logging out")
    try:
        logout_link = WebDriverWait(driver, 10).until(
            EC.element_to_be_clickable((By.XPATH, '//a[contains(@href, "logout.php")]'))
        )
        logout_link.click()
        print("Clicked Logout link")
    except:
        print("Could not find Logout link, trying direct navigation")
        driver.get("http://localhost:8000/uiurp/logout.php")
    
    time.sleep(3)
    print("Current URL:", driver.current_url)
    print("Page Title:", driver.title)

finally:
    # Wait before closing browser
    print("\nTest completed. Waiting for 5 seconds before closing browser...")
    time.sleep(5)
    driver.quit() 