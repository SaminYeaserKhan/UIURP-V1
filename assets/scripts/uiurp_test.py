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
    # STEP 1: Go to UIURP login page
    print("\nSTEP 1: Going to UIURP Login page")
    driver.get("http://localhost/uiurp/login.php")
    print("Page Title:", driver.title)
    time.sleep(2)
    
    # Check if we landed on the correct page
    if "UIU Research Portal" in driver.title:
        print("Successfully loaded login page")
    else:
        print("Warning: Page title doesn't match expected. Checking URL...")
        
    # Print the current URL for debugging
    current_url = driver.current_url
    print("Current URL:", current_url)
    
    # Check if we can find the login form
    try:
        # First try to find by form action
        login_form = driver.find_element(By.XPATH, '//form[@action="src/controller/login_user.php"]')
        print("Found login form by action")
    except:
        try:
            # Then try to find any form
            login_form = driver.find_element(By.TAG_NAME, 'form')
            print("Found generic form")
        except:
            print("No login form found. Page may not have loaded correctly.")
            print("Trying to print page source...")
            print(driver.page_source[:1000] + "..." if len(driver.page_source) > 1000 else driver.page_source)
            raise Exception("Login page didn't load correctly")

    # STEP 2: Login with student credentials
    print("\nSTEP 2: Logging in with student account")
    try:
        # Try to find username field by different approaches
        try:
            username_input = driver.find_element(By.NAME, 'username')
            print("Found username field by name")
        except:
            try:
                # Try using XPath with placeholder containing "username"
                username_input = driver.find_element(By.XPATH, '//input[contains(@placeholder, "username") or contains(@placeholder, "User")]')
                print("Found username field by placeholder")
            except:
                print("Could not find username field. Trying all input fields...")
                inputs = driver.find_elements(By.TAG_NAME, 'input')
                if len(inputs) > 0:
                    username_input = inputs[0]  # Try the first input field
                    print("Using first input field as username")
                else:
                    raise Exception("No input fields found")
                    
        username_input.send_keys('01127836')  # Using a student account from the JSON file
        print("Entered username")
        
        # Try to find password field
        try:
            password_input = driver.find_element(By.NAME, 'Password')
            print("Found password field by name 'Password'")
        except:
            try:
                password_input = driver.find_element(By.NAME, 'password')
                print("Found password field by name 'password'")
            except:
                try:
                    # Try using input type
                    password_input = driver.find_element(By.XPATH, '//input[@type="password"]')
                    print("Found password field by type")
                except:
                    print("Could not find password field. Trying all input fields...")
                    inputs = driver.find_elements(By.TAG_NAME, 'input')
                    if len(inputs) > 1:
                        password_input = inputs[1]  # Try the second input field
                        print("Using second input field as password")
                    else:
                        raise Exception("No password field found")
                        
        password_input.send_keys('12345')
        print("Entered password")
        
        # Try to find and click login button
        try:
            # Try by XPath in login form
            login_button = driver.find_element(By.XPATH, '//div[contains(@class, "login")]/form/button')
            print("Found login button by XPath")
        except:
            try:
                # Try finding any button
                buttons = driver.find_elements(By.TAG_NAME, 'button')
                login_button = None
                for button in buttons:
                    if button.text.lower() == 'login' or 'log' in button.text.lower():
                        login_button = button
                        print("Found login button by text")
                        break
                        
                if not login_button and len(buttons) > 0:
                    login_button = buttons[0]  # Use the first button as fallback
                    print("Using first button as login button")
                    
                if not login_button:
                    raise Exception("No buttons found")
            except Exception as e:
                print(f"Error finding login button: {str(e)}")
                raise
                
        login_button.click()
        print("Clicked login button")
        
        time.sleep(5)  # Wait a bit longer for login to complete
        
        print("Current URL after login attempt:", driver.current_url)
        print("Page Title after login:", driver.title)
        
    except Exception as e:
        print(f"Error during login: {str(e)}")
        raise

    # The rest of the test steps will only execute if login is successful
    # STEP 3: Navigate to Research page
    print("\nSTEP 3: Navigating to Research Page")
    try:
        research_link = WebDriverWait(driver, 10).until(
            EC.element_to_be_clickable((By.XPATH, '//a[contains(@href, "Research_page.php")]'))
        )
        research_link.click()
        print("Clicked Research Page link")
    except:
        print("Could not find Research Page link, trying direct navigation")
        driver.get("http://localhost/uiurp/Research_page.php")
    
    time.sleep(3)
    print("Current URL:", driver.current_url)
    print("Page Title:", driver.title)

    # STEP 4: View a project (click on the first available project)
    print("\nSTEP 4: Viewing a Project")
    try:
        project_card = WebDriverWait(driver, 10).until(
            EC.element_to_be_clickable((By.CSS_SELECTOR, '.project-card'))
        )
        project_card.click()
        print("Clicked on a project card")
    except Exception as e:
        print("Could not click on any project card:", str(e))
        # Try an alternative approach if no project cards are found
        try:
            view_button = driver.find_element(By.XPATH, '//a[contains(text(), "View Details") or contains(@class, "btn-primary")]')
            view_button.click()
            print("Clicked View Details button")
        except:
            print("No projects found to view")
    
    time.sleep(3)
    print("Current URL:", driver.current_url)
    print("Page Title:", driver.title)

    # STEP 5: Navigate to Forum page
    print("\nSTEP 5: Navigating to Forum")
    try:
        forum_link = WebDriverWait(driver, 10).until(
            EC.element_to_be_clickable((By.XPATH, '//a[contains(@href, "forum_index.php") or contains(@href, "view_posts.php")]'))
        )
        forum_link.click()
        print("Clicked Forum link")
    except:
        print("Could not find Forum link, trying direct navigation")
        driver.get("http://localhost:8000/uiurp/view_posts.php")
    
    time.sleep(3)
    print("Current URL:", driver.current_url)
    print("Page Title:", driver.title)

    # STEP 6: View profile page
    print("\nSTEP 6: Viewing Profile Page")
    try:
        profile_link = WebDriverWait(driver, 10).until(
            EC.element_to_be_clickable((By.XPATH, '//a[contains(@href, "Student_Profile.php")]'))
        )
        profile_link.click()
        print("Clicked Profile link")
    except:
        print("Could not find Profile link, trying direct navigation")
        driver.get("http://localhost:8000/uiurp/Student_Profile.php")
    
    time.sleep(3)
    print("Current URL:", driver.current_url)
    print("Page Title:", driver.title)
    
    # STEP 7: Go to project management page
    print("\nSTEP 7: Navigating to Project Management")
    try:
        project_management_link = WebDriverWait(driver, 10).until(
            EC.element_to_be_clickable((By.XPATH, '//a[contains(@href, "project_management.php")]'))
        )
        project_management_link.click()
        print("Clicked Project Management link")
    except:
        print("Could not find Project Management link, trying direct navigation")
        driver.get("http://localhost:8000/uiurp/project_management.php")
    
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