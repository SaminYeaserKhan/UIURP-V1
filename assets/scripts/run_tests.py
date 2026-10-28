#!/usr/bin/env python3
import subprocess
import time
import os
import sys

def run_test(test_file):
    """Run a single test file and return the result"""
    print(f"\n{'='*80}")
    print(f"Running test: {test_file}")
    print(f"{'='*80}\n")
    
    try:
        result = subprocess.run(['python3', test_file], check=False)
        if result.returncode == 0:
            print(f"\n✅ Test {test_file} completed successfully")
            return True
        else:
            print(f"\n❌ Test {test_file} failed with exit code {result.returncode}")
            return False
    except Exception as e:
        print(f"\n❌ Error running test {test_file}: {str(e)}")
        return False

def main():
    # Get the directory of this script
    script_dir = os.path.dirname(os.path.abspath(__file__))
    
    # Test files to run
    test_files = [
        os.path.join(script_dir, "simple_test.py"),
        os.path.join(script_dir, "uiurp_navigation_test.py")
    ]
    
    # Verify that test server is running on port 8000
    print("Checking if the UIURP server is running on port 8000...")
    try:
        import requests
        try:
            response = requests.get("http://localhost:8000/uiurp/", timeout=5)
            if response.status_code == 200:
                print("✅ Server is running on port 8000")
            else:
                print("⚠️ Server responded with status code", response.status_code)
                proceed = input("Do you want to continue with the tests anyway? (y/n): ")
                if proceed.lower() != 'y':
                    print("Tests aborted.")
                    return
        except requests.exceptions.ConnectionError:
            print("❌ Could not connect to the server on port 8000")
            print("Please make sure the server is running and try again.")
            sys.exit(1)
    except ImportError:
        print("⚠️ Requests library not installed, skipping server check")
    
    # Run the tests
    print("\nRunning tests...")
    results = []
    
    for test_file in test_files:
        result = run_test(test_file)
        results.append((test_file, result))
        time.sleep(1)  # Small pause between tests
    
    # Summary
    print("\n" + "="*80)
    print("Test Summary:")
    print("="*80)
    
    success_count = 0
    for test_file, result in results:
        status = "✅ Passed" if result else "❌ Failed"
        if result:
            success_count += 1
        print(f"{status} - {os.path.basename(test_file)}")
    
    total_tests = len(results)
    print(f"\nResults: {success_count}/{total_tests} tests passed")

if __name__ == "__main__":
    main() 