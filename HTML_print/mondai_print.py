import requests
from bs4 import BeautifulSoup
import json
import re
from github import Github

# GitHubの設定
GITHUB_TOKEN = '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'
REPO_NAME = 'suzu6331/graduate'  # フルネーム形式のリポジトリ名
FILE_PATH = 'data/questions.json'
COMMIT_MESSAGE = 'Add scraped questions'

# 問題を取得する関数
def fetch_questions(url):
    response = requests.get(url)
    response.encoding = 'utf-8'
    soup = BeautifulSoup(response.text, 'html.parser')

    mondai_elements = soup.find_all('div', id='mondai')  # idがmondaiのdiv要素を取得


    # 取得したHTMLの一部を出力して確認
    print(soup.prettify())

    print(mondai_elements)

# GitHubに問題をアップロードする関数
def upload_to_github(token, repo_name, file_path, commit_message, content):
    g = Github(token)
    try:
        repo = g.get_repo(repo_name)

        # ファイルの存在をチェックして更新、または新規作成
        try:
            contents = repo.get_contents(file_path)
            repo.update_file(file_path, commit_message, content, contents.sha)
            print(f"Successfully updated {file_path} in GitHub.")
        except:
            repo.create_file(file_path, commit_message, content)
            print(f"Successfully created {file_path} in GitHub.")
    except Exception as e:
        print(e)
        print("Failed to upload to GitHub.")

# メインの実行部分
if __name__ == "__main__":
    url = 'https://www.fe-siken.com/kakomon/04_menjo/q3.html'
    questions = fetch_questions(url)
    print(questions)  # 取得した質問を出力して確認
    json_data = json.dumps({"quizzes": questions}, ensure_ascii=False, indent=4)
