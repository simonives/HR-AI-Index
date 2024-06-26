<?php

namespace Drupal\workspaces;

/**
 * Provides an interface for managing Workspaces.
 */
interface WorkspaceManagerInterface {

  /**
   * Determines whether a workspace is active in the current request.
   *
   * @return bool
   *   TRUE if a workspace is active, FALSE otherwise.
   */
  public function hasActiveWorkspace();

  /**
   * Gets the active workspace.
   *
   * @return \Drupal\workspaces\WorkspaceInterface
   *   The active workspace entity object.
   */
  public function getActiveWorkspace();

  /**
   * Sets the active workspace via the workspace negotiators.
   *
   * @param \Drupal\workspaces\WorkspaceInterface $workspace
   *   The workspace to set as active.
   *
   * @return $this
   *
   * @throws \Drupal\workspaces\WorkspaceAccessException
   *   Thrown when the current user doesn't have access to view the workspace.
   */
  public function setActiveWorkspace(WorkspaceInterface $workspace);

  /**
   * Unsets the active workspace via the workspace negotiators.
   *
   * @return $this
   */
  public function switchToLive();

  /**
   * Executes the given callback function in the context of a workspace.
   *
   * @param string $workspace_id
   *   The ID of a workspace.
   * @param callable $function
   *   The callback to be executed.
   *
   * @return mixed
   *   The callable's return value.
   */
  public function executeInWorkspace($workspace_id, callable $function);

  /**
   * Executes the given callback function without any workspace context.
   *
   * @param callable $function
   *   The callback to be executed.
   *
   * @return mixed
   *   The callable's return value.
   */
  public function executeOutsideWorkspace(callable $function);

  /**
   * Deletes the revisions associated with deleted workspaces.
   */
  public function purgeDeletedWorkspacesBatch();

}
