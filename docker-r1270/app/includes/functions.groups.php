<?php
/**
 * Check if a group id exists on the database.
 * Used on the Edit group page.
 *
 * @return bool
 */
function group_exists_id($id)
{
	global $dbh;
	$statement = $dbh->prepare("SELECT * FROM " . TABLE_GROUPS . " WHERE id=:id");
	$statement->bindParam(':id', $id, PDO::PARAM_INT);
	$statement->execute();
	if ( $statement->rowCount() > 0 ) {
		return true;
	}
	else {
		return false;
	}
}

/**
 * Get all the group information knowing only the id
 *
 * @return array
 */
function get_group_by_id($id)
{
	global $dbh;
	$statement = $dbh->prepare("SELECT * FROM " . TABLE_GROUPS . " WHERE id=:id");
	$statement->bindParam(':id', $id, PDO::PARAM_INT);
	$statement->execute();
	$statement->setFetchMode(PDO::FETCH_ASSOC);

	while ( $row = $statement->fetch() ) {
		$information = array(
							'id'			=> html_output($row['id']),
							'created_by'	=> html_output($row['created_by']),
							'created_date'	=> html_output($row['timestamp']),
							'name'			=> html_output($row['name']),
							'description'	=> html_output($row['description']),
							'public'		=> html_output($row['public']),
							'public_token'	=> html_output($row['public_token']),
						);
		if ( !empty( $information ) ) {
			return $information;
		}
		else {
			return false;
		}
	}
}

/**
 * Return an array of existing groups
 * @todo add limit and order to the query
 * @todo use Group class on response
 */
function get_groups($arguments)
{
    global $dbh;

    $group_ids	= !empty( $arguments['group_ids'] ) ? $arguments['group_ids'] : array();
    $group_ids	= is_array( $group_ids ) ? $group_ids : array( $group_ids );
    $is_public	= !empty( $arguments['public'] ) ? $arguments['public'] : '';
    $created_by	= !empty( $arguments['created_by'] ) ? $arguments['created_by'] : '';
    $search		= !empty( $arguments['search'] ) ? $arguments['search'] : '';

    $groups = array();
    $query = "SELECT * FROM " . TABLE_GROUPS;

    $parameters = array();
    if ( !empty( $group_ids ) ) {
        $parameters[] = "FIND_IN_SET(id, :ids)";
    }
    if ( !empty( $is_public ) ) {
        $parameters[] = "public=:public";
    }
    if ( !empty( $created_by ) ) {
        $parameters[] = "created_by=:created_by";
    }
    if ( !empty( $search ) ) {
        $parameters[] = "(name LIKE :name OR description LIKE :description)";
    }
    
    if ( !empty( $parameters ) ) {
        $p = 1;
        foreach ( $parameters as $parameter ) {
            if ( $p == 1 ) {
                $connector = " WHERE ";
            }
            else {
                $connector = " AND ";
            }
            $p++;
            
            $query .= $connector . $parameter;
        }
    }

    $statement = $dbh->prepare($query);

    if ( !empty( $group_ids ) ) {
        $group_ids = implode( ',', $group_ids );
        $statement->bindParam(':ids', $group_ids);
    }
    if ( !empty( $is_public ) ) {
        switch ( $is_public ) {
            case 'true':
                $pub = 1;
                break;
            case 'false':
                $pub = 0;
                break;
        }
        $statement->bindValue(':public', $pub, PDO::PARAM_INT);
    }
    if ( !empty( $created_by ) ) {
        $statement->bindParam(':created_by', $created_by);
    }
    if ( !empty( $search ) ) {
        $search_value = '%' . $search . '%';
        $statement->bindValue(':name', $search_value);
        $statement->bindValue(':description', $search_value);
    }
    
    $statement->execute();
    $statement->setFetchMode(PDO::FETCH_ASSOC);
    while( $data_group = $statement->fetch() ) {
        $all_groups[$data_group['id']] = array(
            'id'            => $data_group['id'],
            'name'          => $data_group['name'],
            'description'   => $data_group['description'],
            'created_by'    => $data_group['created_by'],
            'public'        => $data_group['public'],
            'public_token'  => $data_group['public_token'],
        );
    }
    
    if ( !empty($all_groups) > 0 ) {
        return $all_groups;
    }
    else {
        return array();
    }
}
