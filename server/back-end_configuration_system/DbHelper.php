<?php
class DbHelper {
    private $pdo;
    private $table;

    // 初始化数据库连接
    public function __construct($host,$port =3306, $dbname, $tableName, $username, $password) {
        // 验证表名，防止SQL注入
        if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $tableName)) {
            throw new InvalidArgumentException("Invalid table name");
        }

        try {
            $this->table = $tableName;
            $this->pdo = new PDO(
                "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4",
                $username,
                $password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]
            );
        } catch (PDOException $e) {
            throw new Exception("数据库连接失败: " . $e->getMessage());
        }
    }

    // ========== 增（Create） ==========
    public function insert($data) {
        try {
            $sql = "INSERT INTO `".$this->table."` (title, url, packageName, icon, remark, modification, accounts) 
                    VALUES (:title, :url, :packageName, :icon, :remark, :modification, :accounts)";

            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':title', $data['title']);
            $stmt->bindParam(':url', $data['url']);
            $stmt->bindParam(':packageName', $data['packageName']);
            $stmt->bindParam(':icon', $data['icon']);
            $stmt->bindParam(':remark', $data['remark']);
            $stmt->bindParam(':modification', $data['modification']);
            $stmt->bindParam(':accounts', $data['accounts']);

            return $stmt->execute();
        } catch (PDOException $e) {
            throw new Exception("插入数据失败: " . $e->getMessage());
        }
    }

    // ========== 删（Delete） ==========
    public function delete($id) {
        try {
            $sql = "DELETE FROM `".$this->table."` WHERE id = :id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            throw new Exception("删除数据失败: " . $e->getMessage());
        }
    }

    // ========== 查（Read） ==========
    // 查询单条记录
    public function find($id) {
        try {
            $sql = "SELECT * FROM `".$this->table."` WHERE id = :id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetch();
        } catch (PDOException $e) {
            throw new Exception("查询数据失败: " . $e->getMessage());
        }
    }

    // 查询所有记录
    public function findAll() {
        try {
            $sql = "SELECT * FROM `".$this->table."`";
            $stmt = $this->pdo->query($sql);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            throw new Exception("查询所有数据失败: " . $e->getMessage());
        }
    }

    // 按条件查询（示例：按 title 模糊搜索）
    public function searchByTitle($keyword) {
        try {
            $sql = "SELECT * FROM `".$this->table."` WHERE title LIKE :keyword";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':keyword', "$keyword");
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            throw new Exception("按标题搜索失败: " . $e->getMessage());
        }
    }

    public function searchByPackageName($packageName) {
        try {
            $sql = "SELECT * FROM `".$this->table."` WHERE packageName LIKE :packageName";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':packageName', "$packageName");
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            throw new Exception("按包名搜索失败: " . $e->getMessage());
        }
    }

    public function searchByUrl($url) {
        try {
            $sql = "SELECT * FROM `".$this->table."` WHERE url LIKE :url";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':url', "$url");
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            throw new Exception("按URL搜索失败: " . $e->getMessage());
        }
    }

    public function searchByRemark($remark) {
        try {
            $sql = "SELECT * FROM `".$this->table."` WHERE remark LIKE :remark";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':remark', "%$remark%");
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            throw new Exception("按备注搜索失败: " . $e->getMessage());
        }
    }

    // ========== 改（Update） ==========
    public function update($id, $data) {
        try {
            $sql = "UPDATE `".$this->table."` SET 
                    title = :title, 
                    url = :url, 
                    packageName = :packageName, 
                    icon = :icon, 
                    remark = :remark,
                    modification = :modification, 
                    accounts = :accounts 
                    WHERE id = :id";

            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->bindParam(':title', $data['title']);
            $stmt->bindParam(':url', $data['url']);
            $stmt->bindParam(':packageName', $data['packageName']);
            $stmt->bindParam(':icon', $data['icon']);
            $stmt->bindParam(':remark', $data['remark']);
            $stmt->bindParam(':modification', $data['modification']);
            $stmt->bindParam(':accounts', $data['accounts']);

            return $stmt->execute();
        } catch (PDOException $e) {
            throw new Exception("更新数据失败: " . $e->getMessage());
        }
    }

    public function updateAccountsEmpty() {
        try {
            $sql = "UPDATE `".$this->table."` SET `accounts` = '' WHERE `id` >= 0";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute();
        } catch (PDOException $e) {
            throw new Exception("清空账户数据失败: " . $e->getMessage());
        }
    }
    public function updateAccounts($id,$new_accounts) {
        try {
            $sql = "UPDATE `".$this->table."` SET `accounts` = :accounts WHERE `id` = :id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->bindParam(':accounts', $new_accounts);
            return $stmt->execute();
        } catch (PDOException $e) {
            throw new Exception("重置账户数据失败: " . $e->getMessage());
        }
    }
    // ========== 事务处理（可选） ==========
    public function beginTransaction() {
        return $this->pdo->beginTransaction();
    }

    public function commit() {
        return $this->pdo->commit();
    }

    public function rollBack() {
        return $this->pdo->rollBack();
    }

    // ========== 其他实用方法 ==========
    public function countAll() {
        try {
            $sql = "SELECT COUNT(*) as total FROM `".$this->table."`";
            $stmt = $this->pdo->query($sql);
            return $stmt->fetchColumn();
        } catch (PDOException $e) {
            throw new Exception("统计记录数失败: " . $e->getMessage());
        }
    }

    public function existsByTitle($title) {
        try {
            $sql = "SELECT COUNT(*) FROM `".$this->table."` WHERE title = :title";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':title', $title);
            $stmt->execute();
            return $stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            throw new Exception("检查标题存在性失败: " . $e->getMessage());
        }
    }
}
?>
