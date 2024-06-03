import requests
from bs4 import BeautifulSoup
import json
from github import Github

# GitHubの設定
GITHUB_TOKEN = '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'
REPO_URL = 'https://github.com/suzu6331/graduate.git'  # 作成したリポジトリのURL
FILE_PATH = 'data/questions.json'  # 保存先のファイルパス
COMMIT_MESSAGE = 'Add scraped questions'

# 問題を取得する関数
def fetch_questions(url):
    response = requests.get(url)
    response.encoding = 'utf-8'
    soup = BeautifulSoup(response.text, 'html.parser')

    questions = []
    question_elements = soup.find_all('div', class_='question')  # クラス名は仮定

    for index, element in enumerate(question_elements):
        mondai = element.find('div', class_='mondai').text
        sentaku_elements = element.find_all('div', class_='sentaku')
        sentaku = [s.text for s in sentaku_elements]
        answer = element.find('div', class_='answer').text

        question = {
            "id": index + 1,
            "mondai": mondai,
            "sentaku": sentaku,
            "answer": int(answer) - 1,  # 1から始まる場合、0から始まるインデックスに調整
            "bunrui": 0  # 仮の分類
        }
        questions.append(question)

    return questions

# JSONファイルに保存する関数
def save_to_json(data, filename):
    with open(filename, 'w', encoding='utf-8') as f:
        json.dump({"quizzes": data}, f, ensure_ascii=False, indent=4)

# GitHubに問題をアップロードする関数
def upload_to_github(token, repo_url, file_path, commit_message, content):
    g = Github(token)
    try:
        repo = g.get_repo(repo_url)

        # questions.json として問題を保存
        save_to_json(json.loads(content)["quizzes"], file_path)

        # GitHubにアップロード
        contents = repo.get_contents(file_path)
        repo.update_file(file_path, commit_message, json.dumps({"quizzes": content}, ensure_ascii=False, indent=4), contents.sha)
        print(f"Successfully uploaded {file_path} to GitHub.")
    except Exception as e:
        print(e)
        print("Failed to upload to GitHub.")

# メインの実行部分
if __name__ == "__main__":
    url = 'https://www.fe-siken.com/kakomon/05_haru/a1.html'
    questions = fetch_questions(url)
    json_data = json.dumps({"quizzes": questions}, ensure_ascii=False, indent=4)
    
    upload_to_github(GITHUB_TOKEN, REPO_URL, FILE_PATH, COMMIT_MESSAGE, json_data)
