<?php
/***************************************************************************
 *
 * Copyright (c) 2015 Baidu.com, Inc. All Rights Reserved
 *
 **************************************************************************/

/**
 * @file Block.php
 * @date 2015/07/02
 * @author chenguang02@baidu.com
 * @brief 
 *
 **/

class Lib_Block {
    /**
     * Holds the system id for the shared memory block
     *
     * @var int
     * @access protected
     */
     public $id;
    /**
     * Holds the shared memory block id returned by shmop_open
     *
     * @var int
     * @access protected
     */
    protected $shmid;
    /**
     * Holds the default permission (octal) that will be used in created memory blocks
     *
     * @var int
     * @access protected
     */
    protected $perms = 0644;
    /**
     * Shared memory block instantiation
     *
     * In the constructor we'll check if the block we're going to manipulate
     * already exists or needs to be created. If it exists, let's open it.
     *
     * @access public
     * @param string $id (optional) ID of the shared memory block you want to manipulate
     */
    public function __construct($id = null)
    {
        if($id === null) {
            $this->id = $this->generateID();
        }
        while ($this->exists($id)) {
            $this->id = $this->generateID();
        }
        $this->shmid = shmop_open($this->id, "c", $this->perms, 64);
    }
    /**
     * Generates a random ID for a shared memory block
     *
     * @access protected
     * @return int Randomly generated ID, between 1 and 65535
     */
    protected function generateID()
    {
        $id = mt_rand(1, 65535);
        return $id;
    }
    /**
     * Checks if a shared memory block with the provided id exists or not
     *
     * In order to check for shared memory existance, we have to open it with
     * reading access. If it doesn't exist, warnings will be cast, therefore we
     * suppress those with the @ operator.
     *
     * @access public
     * @param string $id ID of the shared memory block you want to check
     * @return boolean True if the block exists, false if it doesn't
     */
    public function exists($id)
    {
        $status = @shmop_open($id, "a", 0, 0);
        return $status;
    }
    /**
     * Writes on a shared memory block
     *
     * First we check for the block existance, and if it doesn't, we'll create it. Now, if the
     * block already exists, we need to delete it and create it again with a new byte allocation that
     * matches the size of the data that we want to write there. We mark for deletion,  close the semaphore
     * and create it again.
     *
     * @access public
     * @param string $data The data that you wan't to write into the shared memory block
     */
    public function write($data)
    {
        $size = mb_strlen($data, 'UTF-8');
        return shmop_write($this->shmid, $data, 0);
    }
    /**
     * Reads from a shared memory block
     *
     * @access public
     * @return string The data read from the shared memory block
     */
    public function read()
    {
        $size = shmop_size($this->shmid);
        $data = shmop_read($this->shmid, 0, $size);
        return $data;
    }
    /**
     * Mark a shared memory block for deletion
     *
     * @access public
     */
    public function delete()
    {
        return shmop_delete($this->shmid);
    }
    /**
     * Gets the current shared memory block id
     *
     * @access public
     */
    public function getId()
    {
        return $this->id;
    }
    /**
     * Gets the current shared memory block permissions
     *
     * @access public
     */
    public function getPermissions()
    {
        return $this->perms;
    }
    /**
     * Sets the default permission (octal) that will be used in created memory blocks
     *
     * @access public
     * @param string $perms Permissions, in octal form
     */
    public function setPermissions($perms)
    {
        $this->perms = $perms;
    }
    /**
     * Closes the shared memory block and stops manipulation
     *
     * @access public
     */
    public function __destruct()
    {
        shmop_close($this->shmid);
    }
}//class

/* vim: set expandtab ts=4 sw=4 sts=4 tw=100: */
?>
